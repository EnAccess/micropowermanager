<?php

namespace App\Plugins\WhatsApp\Services;

/**
 * Resolves the bounded set of placeholders supported by WhatsApp templates.
 *
 * Templates are free text authored by operators, so substitution is restricted
 * to an explicit allow-list: a template can never read an arbitrary model
 * attribute just by naming it.
 */
class WhatsAppTemplateVariableResolver {
    /** @var string[] */
    public const ALLOWED_VARIABLES = [
        'customer_name',
        'surname',
        'payment_amount',
        'payment_date',
        'transaction_id',
        'token',
        'token_amount',
        'balance',
        'currency',
        'device_serial',
        'due_date',
    ];

    /**
     * @param array<string, mixed> $variables
     */
    public function resolve(string $body, array $variables): string {
        return (string) preg_replace_callback(
            '/\{\{\s*([a-z0-9_]+)\s*\}\}/i',
            static function (array $matches) use ($variables): string {
                $key = strtolower($matches[1]);

                if (!in_array($key, self::ALLOWED_VARIABLES, true)) {
                    return $matches[0];
                }

                $value = $variables[$key] ?? '';

                return (string) $value;
            },
            $body,
        );
    }
}
