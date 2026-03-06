<?php

namespace Tests\Unit\SmsTransactionParser;

use App\Plugins\SmsTransactionParser\SmsParsing\Parsers\MovitelTransactionParser;
use PHPUnit\Framework\TestCase;

class MovitelTransactionParserTest extends TestCase {
    public function testParsesValidRegexMatches(): void {
        $parser = new MovitelTransactionParser();
        $body = 'ID da transacao PP260101.0930.A12345. Transferiste 1.00MT para conta 840000001. Conteudo: 5566778899.';
        $matches = [
            'amount' => '1.00',
            'transaction_ref' => 'PP260101.0930.A12345',
            'device_serial' => '5566778899',
        ];

        $result = $parser->parse($body, $matches);

        $this->assertNotNull($result);
        $this->assertEquals(1.00, $result->amount);
        $this->assertNull($result->senderPhone);
        $this->assertEquals('PP260101.0930.A12345', $result->transactionReference);
        $this->assertEquals('5566778899', $result->deviceSerial);
        $this->assertEquals('Movitel', $result->providerName);
    }

    public function testReturnsNullWhenRequiredFieldMissing(): void {
        $parser = new MovitelTransactionParser();

        $result = $parser->parse('some body', [
            'amount' => '2500',
        ]);

        $this->assertNull($result);
    }
}
