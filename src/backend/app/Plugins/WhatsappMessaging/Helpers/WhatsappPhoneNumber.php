<?php

namespace App\Plugins\WhatsappMessaging\Helpers;

/**
 * Meta identifies WhatsApp users by a "wa_id": the E.164 number without the leading "+".
 * MPM stores phone numbers in E.164 everywhere else, so this is the single point where the two forms are converted.
 */
final class WhatsappPhoneNumber {
    public static function toWaId(string $e164PhoneNumber): string {
        return ltrim(phone($e164PhoneNumber)->formatE164(), '+');
    }

    public static function fromWaId(string $waId): string {
        return phone('+'.ltrim($waId, '+'))->formatE164();
    }
}
