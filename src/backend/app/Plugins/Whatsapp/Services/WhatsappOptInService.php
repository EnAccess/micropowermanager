<?php

namespace App\Plugins\Whatsapp\Services;

use App\Plugins\Whatsapp\Models\WhatsappOptIn;

class WhatsappOptInService {
    public function __construct(private WhatsappOptIn $optIn) {}

    public static function normalize(string $phone): string {
        return preg_replace('/\D/', '', $phone);
    }

    public function isOptedIn(string $phone): bool {
        return $this->optIn->newQuery()
            ->where('phone', self::normalize($phone))
            ->whereNotNull('opted_in_at')
            ->whereNull('revoked_at')
            ->exists();
    }

    public function optIn(string $phone, string $source = 'operator'): WhatsappOptIn {
        return $this->optIn->newQuery()->updateOrCreate(
            ['phone' => self::normalize($phone)],
            ['opted_in_at' => now(), 'revoked_at' => null, 'source' => $source]
        );
    }
}
