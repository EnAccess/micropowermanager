<?php

namespace App\Plugins\AirtelSmsGateway\Smpp;

use App\Plugins\AirtelSmsGateway\Exceptions\MessageNotSentException;

/**
 * Sends one SMS over SMPP 3.4: bind as transmitter, submit the message, unbind.
 *
 * SMPP is a binary protocol on a plain TCP connection. A packet (PDU) is a 16 byte header
 * (length, command, status, sequence number) followed by the command's fields.
 */
class SmppTransmitter {
    private const int BIND_TRANSMITTER = 0x00000002;
    private const int SUBMIT_SM = 0x00000004;
    private const int UNBIND = 0x00000006;
    private const int ENQUIRE_LINK = 0x00000015;
    private const int RESPONSE_BIT = 0x80000000;
    private const int GENERIC_NACK = 0x80000000;

    private const int HEADER_LENGTH = 16;
    private const int MAX_PDU_LENGTH = 65536;
    private const int INTERFACE_VERSION = 0x34;
    private const string SYSTEM_TYPE = 'WWW';

    private const int TON_INTERNATIONAL = 1;
    private const int TON_ALPHANUMERIC = 5;
    private const int NPI_UNKNOWN = 0;
    private const int NPI_E164 = 1;

    private const int SINGLE_MESSAGE_OCTETS = 160;
    private const int SEGMENT_OCTETS = 152;
    private const int MAX_SEGMENTS = 255;
    private const int TAG_SAR_MSG_REF_NUM = 0x020C;
    private const int TAG_SAR_TOTAL_SEGMENTS = 0x020E;
    private const int TAG_SAR_SEGMENT_SEQNUM = 0x020F;

    private const array STATUS_DESCRIPTIONS = [
        0x08 => 'System error',
        0x0A => 'Invalid source address',
        0x0B => 'Invalid destination address',
        0x0D => 'Bind failed',
        0x0E => 'Invalid password',
        0x0F => 'Invalid system ID',
        0x14 => 'Message queue full',
        0x45 => 'Submitting the message failed',
        0x58 => 'Throttling error, too many messages',
    ];

    /** @var resource|null */
    private $stream;
    private int $sequenceNumber = 0;

    /**
     * @param (\Closure(string $host, int $port, int $timeoutSeconds): resource)|null $connector opens the TCP connection; replaced in tests
     */
    public function __construct(
        private int $timeoutSeconds = 10,
        private ?\Closure $connector = null,
    ) {}

    /**
     * @return string the message ID the SMSC assigned (of the last part, for a message sent in parts)
     */
    public function send(
        string $host,
        int $port,
        string $systemId,
        #[\SensitiveParameter]
        string $password,
        string $sourceAddress,
        string $destinationNumber,
        string $message,
    ): string {
        $this->connect($host, $port);

        try {
            $this->request(self::BIND_TRANSMITTER, $systemId."\0".$password."\0".self::SYSTEM_TYPE."\0"
                .pack('CCC', self::INTERFACE_VERSION, 0, 0)."\0");

            $messageId = '';
            foreach ($this->submitBodies($sourceAddress, $destinationNumber, GsmAlphabet::encode($message)) as $body) {
                $messageId = rtrim($this->request(self::SUBMIT_SM, $body), "\0");
            }

            $this->unbind();

            return $messageId;
        } finally {
            $this->disconnect();
        }
    }

    /**
     * A message longer than one SMS is sent in parts that the phone joins again (SAR tags).
     *
     * @return list<string>
     */
    private function submitBodies(string $sourceAddress, string $destinationNumber, string $encodedMessage): array {
        if (strlen($encodedMessage) <= self::SINGLE_MESSAGE_OCTETS) {
            return [$this->submitBody($sourceAddress, $destinationNumber, $encodedMessage)];
        }

        $segments = $this->splitIntoSegments($encodedMessage);
        if (count($segments) > self::MAX_SEGMENTS) {
            throw new MessageNotSentException('The message is too long to be sent as SMS.');
        }

        $reference = random_int(0, 0xFFFF);
        $bodies = [];
        foreach ($segments as $index => $segment) {
            $bodies[] = $this->submitBody($sourceAddress, $destinationNumber, $segment)
                .pack('nnn', self::TAG_SAR_MSG_REF_NUM, 2, $reference)
                .pack('nnC', self::TAG_SAR_TOTAL_SEGMENTS, 1, count($segments))
                .pack('nnC', self::TAG_SAR_SEGMENT_SEQNUM, 1, $index + 1);
        }

        return $bodies;
    }

    /**
     * @return list<string>
     */
    private function splitIntoSegments(string $encodedMessage): array {
        $segments = [];

        while ($encodedMessage !== '') {
            $length = min(self::SEGMENT_OCTETS, strlen($encodedMessage));
            // An escaped character is two octets that must stay in the same part.
            if ($length < strlen($encodedMessage) && $encodedMessage[$length - 1] === GsmAlphabet::ESCAPE) {
                --$length;
            }
            $segments[] = substr($encodedMessage, 0, $length);
            $encodedMessage = substr($encodedMessage, $length);
        }

        return $segments;
    }

    private function submitBody(string $sourceAddress, string $destinationNumber, string $shortMessage): string {
        return "\0" // service_type
            .pack('CC', self::TON_ALPHANUMERIC, self::NPI_UNKNOWN).$sourceAddress."\0"
            .pack('CC', self::TON_INTERNATIONAL, self::NPI_E164).$destinationNumber."\0"
            .pack('CCC', 0, 0, 0) // esm_class, protocol_id, priority_flag
            ."\0\0" // schedule_delivery_time, validity_period
            .pack('CCCC', 0, 0, 0, 0) // registered_delivery, replace_if_present_flag, data_coding, sm_default_msg_id
            .pack('C', strlen($shortMessage)).$shortMessage;
    }

    private function connect(string $host, int $port): void {
        $connector = $this->connector ?? static function (string $host, int $port, int $timeoutSeconds) {
            $stream = @stream_socket_client("tcp://{$host}:{$port}", $errorCode, $errorMessage, $timeoutSeconds);

            if ($stream === false) {
                throw new MessageNotSentException("Could not connect to the SMSC at {$host}:{$port}: {$errorMessage}");
            }

            return $stream;
        };

        $this->stream = $connector($host, $port, $this->timeoutSeconds);
        $this->sequenceNumber = 0;
        stream_set_timeout($this->stream, $this->timeoutSeconds);
    }

    private function unbind(): void {
        try {
            $this->request(self::UNBIND, '');
        } catch (MessageNotSentException) {
            // The message is already accepted; a failed goodbye must not turn it into a failure.
        }
    }

    private function disconnect(): void {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->stream = null;
    }

    /**
     * Sends a command and returns the body of its response.
     */
    private function request(int $command, string $body): string {
        $sequenceNumber = ++$this->sequenceNumber;
        $this->write($command, 0, $sequenceNumber, $body);

        while (true) {
            [$responseCommand, $status, $responseSequenceNumber, $responseBody] = $this->read();

            // The SMSC may check the connection at any time and expects an answer.
            if ($responseCommand === self::ENQUIRE_LINK) {
                $this->write(self::ENQUIRE_LINK | self::RESPONSE_BIT, 0, $responseSequenceNumber, '');

                continue;
            }

            $answersThisRequest = $responseSequenceNumber === $sequenceNumber
                && in_array($responseCommand, [$command | self::RESPONSE_BIT, self::GENERIC_NACK], true);
            if (!$answersThisRequest) {
                continue;
            }

            if ($status !== 0) {
                $description = self::STATUS_DESCRIPTIONS[$status] ?? 'Rejected';

                throw new MessageNotSentException(sprintf('The SMSC rejected the request: %s (status 0x%08X)', $description, $status));
            }

            return $responseBody;
        }
    }

    private function write(int $command, int $status, int $sequenceNumber, string $body): void {
        $pdu = pack('NNNN', self::HEADER_LENGTH + strlen($body), $command, $status, $sequenceNumber).$body;

        if (@fwrite($this->stream, $pdu) !== strlen($pdu)) {
            throw new MessageNotSentException('The connection to the SMSC was lost while sending.');
        }
    }

    /**
     * @return array{0: int, 1: int, 2: int, 3: string} command, status, sequence number, body
     */
    private function read(): array {
        /** @var array{length: int, command: int, status: int, sequence: int} $header */
        $header = unpack('Nlength/Ncommand/Nstatus/Nsequence', $this->readOctets(self::HEADER_LENGTH));

        // A length outside this range means the stream is out of step; reading on would wait for data that never comes.
        if ($header['length'] < self::HEADER_LENGTH || $header['length'] > self::MAX_PDU_LENGTH) {
            throw new MessageNotSentException('The SMSC sent an unreadable answer.');
        }

        $body = $this->readOctets($header['length'] - self::HEADER_LENGTH);

        return [$header['command'], $header['status'], $header['sequence'], $body];
    }

    private function readOctets(int $length): string {
        $octets = '';

        while (strlen($octets) < $length) {
            $chunk = fread($this->stream, $length - strlen($octets));

            if ($chunk === false || $chunk === '') {
                $timedOut = stream_get_meta_data($this->stream)['timed_out'];

                throw new MessageNotSentException($timedOut ? 'The SMSC did not answer in time.' : 'The SMSC closed the connection.');
            }

            $octets .= $chunk;
        }

        return $octets;
    }
}
