<?php

declare(strict_types=1);

namespace App\Plugins\SmsTransactionParser\SmsParsing\DTO;

class ParsedSmsData {
    public function __construct(
        public readonly float $amount,
        public readonly string $deviceSerial,
        public readonly string $transactionReference,
        public readonly string $providerName,
        public readonly string $rawMessage,
        public readonly ?string $senderPhone = null,
    ) {}
}
