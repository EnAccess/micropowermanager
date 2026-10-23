<?php

namespace App\Plugins\WhatsappMessaging\Helpers;

use Illuminate\Support\Str;

/** Turns an MPM sms_bodies text into a WhatsApp template with named parameters, keeping MPM's variable names. */
class WhatsappTemplateFormat {
    private const string LANGUAGE = 'en';
    private const string VARIABLE_PATTERN = '/\[(\w+)\]/';
    // Meta limits variables per word, so side-by-side variables such as "[name] [surname]" become one parameter.
    private const string VARIABLE_RUN_PATTERN = '/\[\w+\](?:\s+\[\w+\])*/';

    // Meta's reviewers reject templates whose samples are placeholders such as "due_date", so each one looks like a real value.
    private const array SAMPLES = [
        'name' => 'Amina', 'surname' => 'Hassan', 'token' => '1234-5678-9012-3456', 'due_date' => '2026-11-01',
        'amount' => '5000', 'remaining' => '5000', 'transaction_amount' => '5000', 'energy' => '12.5',
        'meter' => '47000123456', 'meter_serial' => '47000123456', 'device_serial' => 'SHS-001234',
        'appliance_type_name' => 'Solar Lamp', 'duration' => '7', 'unit' => 'days',
    ];

    // The text is part of the name: Meta never changes an approved template, so new wording must be a new template.
    public static function name(string $reference, string $smsText, string $companyName): string {
        return 'mpm_'.Str::snake($reference).'_'.substr(md5(self::body($smsText, $companyName)), 0, 8);
    }

    /** @return array<string, mixed> the body of Meta's "create message template" request */
    public static function definition(string $reference, string $smsText, string $companyName): array {
        $examples = array_map(
            fn (string $parameter, array $variables): array => ['param_name' => $parameter, 'example' => implode(' ', array_map(fn (string $variable): string => self::SAMPLES[$variable] ?? '12345', $variables))],
            array_keys(self::parameters($smsText)),
            self::parameters($smsText),
        );

        return [
            'name' => self::name($reference, $smsText, $companyName),
            'language' => self::LANGUAGE,
            'category' => 'UTILITY',
            'parameter_format' => 'NAMED',
            'components' => [['type' => 'BODY', 'text' => self::body($smsText, $companyName), 'example' => ['body_text_named_params' => $examples]]],
        ];
    }

    /**
     * @param array<string, string> $values the body parser's values, keyed by variable name
     *
     * @return array<string, mixed> the message content that sends the template
     */
    public static function message(string $reference, string $smsText, string $companyName, array $values): array {
        return ['type' => 'template', 'template' => [
            'name' => self::name($reference, $smsText, $companyName),
            'language' => ['code' => self::LANGUAGE],
            'components' => [['type' => 'body', 'parameters' => array_map(
                fn (string $parameter, array $variables): array => ['type' => 'text', 'parameter_name' => $parameter, 'text' => self::parameter(implode(' ', array_map(fn (string $variable): string => $values[$variable] ?? '', $variables)))],
                array_keys(self::parameters($smsText)),
                self::parameters($smsText),
            )]],
        ]];
    }

    /**
     * SMS texts are short and terse, which Meta rejects: too few words around the variables, a variable at the start or end, stray spacing.
     * The company name and closing words give the context Meta asks for and keep variables away from both ends.
     */
    private static function body(string $smsText, string $companyName): string {
        $text = (string) preg_replace('/\s+/u', ' ', str_replace('\n', ' ', $smsText));
        $text = (string) preg_replace(['/\(\s*[.,]?\s*(\[\w+\])\s*[.,]?\s*\)/', '/\s+([.,:;)])/', '/\(\s+/'], ['($1)', '$1', '('], $text);
        $text = Str::ucfirst(trim($text));
        $text = preg_match('/[.!?]$/', $text) === 1 ? $text : $text.'.';

        return trim($companyName).': '.preg_replace_callback(self::VARIABLE_RUN_PATTERN, fn (array $run): string => '{{'.self::parameterName($run[0]).'}}', $text).' Thank you.';
    }

    // Meta rejects parameters that are empty or contain newlines, tabs or runs of spaces.
    private static function parameter(string $value): string {
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));

        return $value === '' ? '-' : $value;
    }

    /** @return array<string, list<string>> each parameter once, in the order it first appears, with the variables whose values it joins */
    private static function parameters(string $smsText): array {
        preg_match_all(self::VARIABLE_RUN_PATTERN, str_replace('\n', ' ', $smsText), $runs);
        $parameters = [];
        foreach ($runs[0] as $run) {
            preg_match_all(self::VARIABLE_PATTERN, $run, $variables);
            $parameters[implode('_', $variables[1])] = $variables[1];
        }

        return $parameters;
    }

    private static function parameterName(string $run): string {
        return (string) preg_replace(['/[\[\]]/', '/\s+/'], ['', '_'], $run);
    }
}
