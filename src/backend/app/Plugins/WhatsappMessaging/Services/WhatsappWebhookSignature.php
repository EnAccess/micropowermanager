<?php

namespace App\Plugins\WhatsappMessaging\Services;

use Illuminate\Http\Request;

class WhatsappWebhookSignature {
    private const HEADER = 'X-Hub-Signature-256';

    /**
     * Meta signs the raw body bytes; re-encoding the parsed JSON changes them, so
     * the HMAC must be computed over getContent().
     */
    public function isValid(Request $request, ?string $appSecret): bool {
        $signature = $request->header(self::HEADER);

        if (empty($appSecret) || !is_string($signature) || $signature === '') {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expected, $signature);
    }
}
