<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Reverses 2026_10_22_000000_improve_default_sms_bodies: that wording was written to pass Meta's template review,
// and WhatsApp templates are now written in WhatsApp Manager, so the SMS texts go back to their original defaults.
return new class extends Migration {
    // reference => [improved default, original default]
    private const array TEXTS = [
        'ApplianceRateReminder' => [
            'The next payment for your [appliance_type_name] of [remaining] is due on [due_date].',
            'the next rate of  [appliance_type_name] ( . [remaining] . ) is due on [due_date]',
        ],
        'OverdueApplianceRateReminder' => [
            'Your payment for [appliance_type_name] of [remaining] was due on [due_date]. Please pay soon so you can keep buying energy.',
            'you forgot to pay the rate of [appliance_type_name] ( [remaining] )  on [due_date]. Please pay it as soon as possible, unless you wont be able to buy energy.',
        ],
        'TokenConfirmationSHS' => [
            'Dear [name] [surname], your payment of [amount] is confirmed. Device [device_serial] is now active for [duration] [unit]. Your token is [token].',
            'Dear [name] [surname], your transaction is confirmed. Device Serial: [device_serial]. Token: [token], Duration: [duration] [unit]. Amount: [amount].',
        ],
        'ResendInformation' => [
            'Your last purchase for meter [meter]: token [token], [energy] kWh, service charge [amount].',
            'Meter: [meter] , [token]  Unit [energy] KWH. Service Charge: [amount]',
        ],
    ];

    public function up(): void {
        foreach (self::TEXTS as $reference => [$improvedText, $originalText]) {
            $this->replace($reference, $improvedText, $originalText);
        }
    }

    public function down(): void {
        foreach (self::TEXTS as $reference => [$improvedText, $originalText]) {
            $this->replace($reference, $originalText, $improvedText);
        }
    }

    private function replace(string $reference, string $from, string $to): void {
        // body is editable in Settings; only a body still matching the default is replaced, so a tenant's own wording is kept.
        DB::connection('tenant')->table('sms_bodies')
            ->where('reference', $reference)
            ->where('body', $from)
            ->update(['body' => $to, 'updated_at' => Carbon::now()]);

        // place_holder is only the editor's hint text, so it is restored even where the body was customized.
        DB::connection('tenant')->table('sms_bodies')
            ->where('reference', $reference)
            ->update(['place_holder' => $to, 'updated_at' => Carbon::now()]);
    }
};
