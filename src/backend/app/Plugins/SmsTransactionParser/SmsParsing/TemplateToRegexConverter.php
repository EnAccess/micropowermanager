<?php

declare(strict_types=1);

namespace App\Plugins\SmsTransactionParser\SmsParsing;

class TemplateToRegexConverter {
    /**
     * Regex patterns for each known placeholder variable.
     *
     * @var array<string, string>
     */
    private const VARIABLE_PATTERNS = [
        'transaction_ref' => '[A-Za-z0-9.]+',
        'amount' => '[\d,]+(?:\.\d{1,2})?',
        'sender_phone' => '[\d+\s\-]+',
        'device_serial' => '[A-Za-z0-9]+',
    ];

    private const WILDCARD_PATTERN = '[\s\S]*?';

    /**
     * Convert a human-readable template with [variable] placeholders
     * into a regex with named capture groups.
     *
     * Use [*] as a wildcard to match any text between fields.
     *
     * Example input:  "Confirmed [transaction_ref].[*]amount of [amount]MT[*]reference [device_serial][*]"
     * Example output: "/Confirmed\s+(?P<transaction_ref>[A-Za-z0-9.]+)\.[\s\S]*?amount\s+of\s+(?P<amount>[\d,]+(?:\.\d{1,2})?)MT[\s\S]*?reference\s+(?P<device_serial>[A-Za-z0-9]+)[\s\S]*?/si"
     */
    public function convert(string $template): string {
        // Escape regex special chars in the literal parts, but preserve the [variable] placeholders first
        $placeholderMap = [];
        $index = 0;

        // Replace placeholders (including [*] wildcard) with unique tokens before escaping
        $tokenized = preg_replace_callback('/\[(\w+|\*)]/', function (array $matches) use (&$placeholderMap, &$index): string {
            $token = '___PLACEHOLDER_'.$index.'___';
            $placeholderMap[$token] = $matches[1];
            ++$index;

            return $token;
        }, $template);

        // Escape regex special characters in the literal text
        $escaped = preg_quote($tokenized, '/');

        // Collapse whitespace runs to flexible whitespace matchers
        $escaped = preg_replace('/\s+/', '\\s+', $escaped);

        // Replace tokens back with named capture groups (or wildcards for [*])
        foreach ($placeholderMap as $token => $variable) {
            $quotedToken = preg_quote($token, '/');

            if ($variable === '*') {
                $escaped = str_replace($quotedToken, self::WILDCARD_PATTERN, $escaped);
            } else {
                $variablePattern = self::VARIABLE_PATTERNS[$variable] ?? '.+?';
                $escaped = str_replace($quotedToken, '(?P<'.$variable.'>'.$variablePattern.')', $escaped);
            }
        }

        return '/'.$escaped.'/si';
    }

    /**
     * @return list<string>
     */
    public function getRequiredVariables(): array {
        return array_keys(self::VARIABLE_PATTERNS);
    }
}
