<?php

namespace App\Plugins\WhatsApp\Services;

use App\Models\Person\Person;
use App\Plugins\WhatsApp\Models\WhatsAppOptOut;
use Illuminate\Support\Facades\Log;

class WhatsAppOptOutService {
    /** @var string[] */
    public const OPTOUT_KEYWORDS = [
        'STOP',
        'UNSUBSCRIBE',
        'CANCEL',
        'QUIT',
        'ARRETER',
        'HUSA',
        'OPT-OUT',
        'OPTOUT',
    ];

    /** @var string[] */
    public const OPTIN_KEYWORDS = [
        'START',
        'UNSTOP',
        'SUBSCRIBE',
        'YES',
        'OUI',
        'RE-SUBSCRIBE',
    ];

    /**
     * Check if a recipient or person has opted out of WhatsApp or explicitly prefers SMS.
     */
    public function isOptedOut(?string $phone, ?Person $person = null): bool {
        // 1. Check person-level onboarding profile preference
        if ($person instanceof Person) {
            $onboarding = $person->onboarding_json;
            if (is_array($onboarding)) {
                if (($onboarding['preferred_channel'] ?? null) === WhatsAppOptOut::CHANNEL_SMS) {
                    return true;
                }
                if (!empty($onboarding['whatsapp_opt_out'])) {
                    return true;
                }
            }
        }

        // 2. Check phone-level opt-out registry
        if ($phone === null || $phone === '') {
            return false;
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($cleanPhone)) {
            return false;
        }

        try {
            return WhatsAppOptOut::query()
                ->where('phone', $cleanPhone)
                ->where('opted_out', true)
                ->exists();
        } catch (\Throwable) {
            // If table does not exist or connection fails, do not block
            return false;
        }
    }

    /**
     * Opt out a phone number from WhatsApp, redirecting notifications to SMS.
     */
    public function optOut(string $phone, string $reason = 'customer_request', ?int $personId = null): WhatsAppOptOut {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        $optOut = WhatsAppOptOut::query()->updateOrCreate(
            ['phone' => $cleanPhone],
            [
                'person_id' => $personId,
                'preferred_channel' => WhatsAppOptOut::CHANNEL_SMS,
                'opted_out' => true,
                'reason' => $reason,
                'opted_out_at' => now(),
            ],
        );

        Log::info('Customer opted out of WhatsApp notifications', [
            'phone' => $cleanPhone,
            'reason' => $reason,
            'person_id' => $personId,
        ]);

        return $optOut;
    }

    /**
     * Opt in / re-subscribe a phone number to WhatsApp notifications.
     */
    public function optIn(string $phone): void {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        WhatsAppOptOut::query()
            ->where('phone', $cleanPhone)
            ->update([
                'opted_out' => false,
                'preferred_channel' => WhatsAppOptOut::CHANNEL_WHATSAPP,
                'opted_out_at' => null,
                'reason' => 'opt_in_request',
            ]);

        Log::info('Customer opted in to WhatsApp notifications', [
            'phone' => $cleanPhone,
        ]);
    }

    /**
     * Process an inbound customer text message to detect opt-out / opt-in keywords.
     * Returns a response message if a keyword was recognized, or null otherwise.
     */
    public function handleInboundKeyword(string $phone, string $text): ?string {
        $normalized = strtoupper(trim($text));

        if (in_array($normalized, self::OPTOUT_KEYWORDS, true)) {
            $this->optOut($phone, 'inbound_stop_keyword');

            return 'You have been unsubscribed from WhatsApp notifications. You will receive electricity and payment updates via SMS instead. Reply START to re-subscribe anytime.';
        }

        if (in_array($normalized, self::OPTIN_KEYWORDS, true)) {
            $this->optIn($phone);

            return 'You are now subscribed to WhatsApp notifications from MicroPowerManager. Reply STOP anytime to opt out.';
        }

        return null;
    }
}
