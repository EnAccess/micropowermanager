<?php

declare(strict_types=1);

namespace App\Plugins\SmsTransactionParser\SmsParsing\Contracts;

use App\Plugins\SmsTransactionParser\SmsParsing\DTO\ParsedSmsData;

interface ISmsTransactionParser {
    /**
     * @param array<string, string> $regexMatches Named capture groups from the parsing rule regex
     */
    public function parse(string $body, array $regexMatches): ?ParsedSmsData;
}
