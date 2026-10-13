<?php

namespace App\Plugins\WhatsappMessaging\Helpers;

use Illuminate\Support\Str;

/** Turns an MPM sms_bodies text into a WhatsApp template with named parameters, keeping MPM's wording and variable names. */
class WhatsappTemplateFormat {
    private const string LANGUAGE = 'en';
    private const string VARIABLE_PATTERN = '/\[(\w+)\]/';

    public static function name(string $reference): string {
        return 'mpm_'.Str::snake($reference);
    }

    /** @return array<string, mixed> the body of Meta's "create message template" request */
    public static function definition(string $reference, string $smsText): array {
        // Meta rejects a body that ends with a parameter, and its review needs one sample per parameter; the variable name is an honest one.
        $body = (string) preg_replace(self::VARIABLE_PATTERN, '{{$1}}', trim($smsText));
        $examples = array_map(fn (string $variable): array => ['param_name' => $variable, 'example' => $variable], self::variables($smsText));

        return [
            'name' => self::name($reference),
            'language' => self::LANGUAGE,
            'category' => 'UTILITY',
            'parameter_format' => 'NAMED',
            'components' => [['type' => 'BODY', 'text' => str_ends_with($body, '}}') ? $body.'.' : $body, 'example' => ['body_text_named_params' => $examples]]],
        ];
    }

    /**
     * @param array<string, string> $values the body parser's values, keyed by variable name
     *
     * @return array<string, mixed> the message content that sends the template
     */
    public static function message(string $reference, string $smsText, array $values): array {
        return ['type' => 'template', 'template' => [
            'name' => self::name($reference),
            'language' => ['code' => self::LANGUAGE],
            'components' => [['type' => 'body', 'parameters' => array_map(
                fn (string $variable): array => ['type' => 'text', 'parameter_name' => $variable, 'text' => self::parameter($values[$variable] ?? '')],
                self::variables($smsText),
            )]],
        ]];
    }

    // Meta rejects parameters that are empty or contain newlines, tabs or runs of spaces.
    private static function parameter(string $value): string {
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));

        return $value === '' ? '-' : $value;
    }

    /** @return list<string> each variable once, in the order it first appears */
    private static function variables(string $smsText): array {
        preg_match_all(self::VARIABLE_PATTERN, $smsText, $matches);

        return array_values(array_unique($matches[1]));
    }
}
