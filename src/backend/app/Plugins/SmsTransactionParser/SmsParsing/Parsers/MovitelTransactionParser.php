<?php

declare(strict_types=1);

namespace App\Plugins\SmsTransactionParser\SmsParsing\Parsers;

use App\Plugins\SmsTransactionParser\SmsParsing\Contracts\ISmsTransactionParser;
use App\Plugins\SmsTransactionParser\SmsParsing\ParsedSmsData;

class MovitelTransactionParser implements ISmsTransactionParser {
    /**
     * @param array<string, string> $regexMatches
     */
    public function parse(string $body, array $regexMatches): ?ParsedSmsData {
        $amount = $regexMatches['amount'] ?? null;
        $transactionRef = $regexMatches['transaction_ref'] ?? null;
        $deviceSerial = $regexMatches['device_serial'] ?? null;

        if ($amount === null || $transactionRef === null || $deviceSerial === null) {
            return null;
        }

        $amount = (float) str_replace([',', ' '], '', $amount);
        $transactionRef = trim($transactionRef);
        $deviceSerial = trim($deviceSerial);

        if ($amount <= 0 || $transactionRef === '' || $deviceSerial === '') {
            return null;
        }

        $senderPhone = isset($regexMatches['sender_phone'])
            ? preg_replace('/[^0-9+]/', '', $regexMatches['sender_phone'])
            : null;

        if ($senderPhone === '') {
            $senderPhone = null;
        }

        return new ParsedSmsData(
            amount: $amount,
            deviceSerial: $deviceSerial,
            transactionReference: $transactionRef,
            providerName: 'Movitel',
            rawMessage: $body,
            senderPhone: $senderPhone,
        );
    }
}
