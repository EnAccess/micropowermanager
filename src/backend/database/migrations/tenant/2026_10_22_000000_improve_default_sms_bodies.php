<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    // reference => [current default, clearer default]; the variables stay the same, so the body parsers are unchanged.
    private const array TEXTS = [
        'ApplianceRateReminder' => [
            'the next rate of  [appliance_type_name] ( . [remaining] . ) is due on [due_date]',
            'The next payment for your [appliance_type_name] of [remaining] is due on [due_date].',
        ],
        'OverdueApplianceRateReminder' => [
            'you forgot to pay the rate of [appliance_type_name] ( [remaining] )  on [due_date]. Please pay it as soon as possible, unless you wont be able to buy energy.',
            'Your payment for [appliance_type_name] of [remaining] was due on [due_date]. Please pay soon so you can keep buying energy.',
        ],
        'TokenConfirmationSHS' => [
            'Dear [name] [surname], your transaction is confirmed. Device Serial: [device_serial]. Token: [token], Duration: [duration] [unit]. Amount: [amount].',
            'Dear [name] [surname], your payment of [amount] is confirmed. Device [device_serial] is now active for [duration] [unit]. Your token is [token].',
        ],
        'ResendInformation' => [
            'Meter: [meter] , [token]  Unit [energy] KWH. Service Charge: [amount]',
            'Your last purchase for meter [meter]: token [token], [energy] kWh, service charge [amount].',
        ],
    ];

    public function up(): void {
        foreach (self::TEXTS as $reference => [$currentText, $clearerText]) {
            $this->replace($reference, $currentText, $clearerText);
        }
    }

    public function down(): void {
        foreach (self::TEXTS as $reference => [$currentText, $clearerText]) {
            $this->replace($reference, $clearerText, $currentText);
        }
    }

    private function replace(string $reference, string $from, string $to): void {
        // body is editable in Settings; only a body still matching the default is replaced, so a tenant's own wording is kept.
        DB::connection('tenant')->table('sms_bodies')
            ->where('reference', $reference)
            ->where('body', $from)
            ->update(['body' => $to, 'updated_at' => Carbon::now()]);

        // place_holder is only the editor's hint text, so it is refreshed even where the body was customized.
        DB::connection('tenant')->table('sms_bodies')
            ->where('reference', $reference)
            ->update(['place_holder' => $to, 'updated_at' => Carbon::now()]);
    }
};
