<?php

declare(strict_types=1);

namespace App\Plugins\SmsTransactionParser\SmsParsing;

use App\Plugins\SmsTransactionParser\Models\SmsParsingRule;
use App\Plugins\SmsTransactionParser\SmsParsing\DTO\ParsedSmsData;
use App\Plugins\SmsTransactionParser\SmsParsing\Parsers\SmsTransactionParser;

class SmsParserFactory {
    public function __construct(
        private SmsParsingRule $smsParsingRule,
    ) {}

    public function parse(string $body, string $sender): ?ParsedSmsData {
        $rules = $this->smsParsingRule->newQuery()
            ->where('enabled', true)
            ->get();

        foreach ($rules as $rule) {
            if (isset($rule->sender_pattern) && $rule->sender_pattern != '' && !preg_match($rule->sender_pattern, $sender)) {
                continue;
            }

            if (!preg_match($rule->pattern, $body, $matches)) {
                continue;
            }

            $parser = new SmsTransactionParser($rule->provider_name);
            $namedMatches = array_filter($matches, is_string(...), ARRAY_FILTER_USE_KEY);

            return $parser->parse($body, $namedMatches);
        }

        return null;
    }
}
