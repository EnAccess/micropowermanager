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

    /** @var array<string, string> */
    public const ALIASES = [
        'name' => 'customer_name',
        'customer' => 'customer_name',
        'client' => 'customer_name',
        'client_name' => 'customer_name',
        'amount' => 'payment_amount',
        'date' => 'payment_date',
        'meter' => 'device_serial',
        'meter_number' => 'device_serial',
        'serial' => 'device_serial',
        'ref' => 'transaction_id',
        'reference' => 'transaction_id',
        'token_code' => 'token',
    ];

    /**
     * @param array<string, mixed> $variables
     */
    public function resolve(string $body, array $variables): string {
        return (string) preg_replace_callback(
            '/\{{1,2}\s*([a-z0-9_]+)\s*\}{1,2}/i',
            function (array $matches) use ($variables): string {
                $rawKey = strtolower($matches[1]);
                $key = self::ALIASES[$rawKey] ?? $rawKey;

                if (!in_array($key, self::ALLOWED_VARIABLES, true)) {
                    return $matches[0];
                }

                if (array_key_exists($key, $variables)) {
                    return (string) $variables[$key];
                }

                if (array_key_exists($rawKey, $variables)) {
                    return (string) $variables[$rawKey];
                }

                return '';
            },
            $body,
        );
    }
}
