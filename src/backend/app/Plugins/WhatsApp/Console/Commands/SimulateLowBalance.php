<?php

namespace App\Plugins\WhatsApp\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Events\ApplianceRateReminderEvent;
use App\Models\ApplianceRate;
use App\Models\Person\Person;
use App\Plugins\WhatsApp\Models\WhatsAppMessage;
use App\Plugins\WhatsApp\Services\WhatsAppMessageService;

class SimulateLowBalance extends AbstractSharedCommand {
    protected $signature = 'whatsapp:simulate-low-balance
                            {phone? : Recipient phone number (e.g. +250791676207)}
                            {--rate= : Appliance rate ID to simulate; defaults to the earliest unpaid rate}
                            {--overdue : Send the overdue reminder variant instead of the upcoming one}
                            {--fail : Simulate WhatsApp failure to demonstrate SMS fallback}';

    protected $description = 'Simulate a low-balance reminder for demo: WhatsApp primary & SMS fallback';

    public function handle(): void {
        $phone = $this->argument('phone') ?: '+250791676207';
        $overdue = (bool) $this->option('overdue');
        $shouldFail = (bool) $this->option('fail');
        $reminderKind = $overdue ? 'overdue' : 'upcoming';

        $rate = $this->resolveRate($reminderKind);

        if (!$rate instanceof ApplianceRate) {
            $this->error('No unpaid appliance rate with a reachable customer found. Pass --rate=<id>.');

            return;
        }

        $person = $rate->appliancePerson?->person;

        if (!$person instanceof Person) {
            $this->error("Appliance rate {$rate->id} has no person attached.");

            return;
        }

        // WhatsApp resolves the customer's primary address while SMS resolves
        // the first one, so point every address at the presenter's phone.
        foreach ($person->addresses as $address) {
            $address->update(['phone' => $phone]);
        }

        $customerName = trim($person->name.' '.$person->surname);
        $this->info("Simulating {$reminderKind} low-balance reminder for {$customerName} (rate {$rate->id}, balance {$rate->remaining}, due {$rate->due_date})...");

        if ($shouldFail) {
            $message = WhatsAppMessage::query()->create([
                'notification_type' => WhatsAppMessage::TYPE_LOW_BALANCE_WARNING,
                'recipient_phone' => preg_replace('/[^0-9]/', '', $phone),
                'recipient_name' => $customerName,
                'person_id' => $person->id,
                'body' => "Hello {$customerName}, your outstanding balance is {$rate->remaining}. Please settle it before {$rate->due_date}.",
                'reference' => 'appliance_rate:'.$rate->id.':'.$reminderKind,
                'status' => WhatsAppMessage::STATUS_FAILED,
                'error_message' => 'Simulated WhatsApp delivery failure (network timeout / recipient unreachable)',
            ]);
            $this->error("✗ WhatsApp delivery failed [Message ID: {$message->id}]");
            $this->line("Run 'php artisan whatsapp:send-sms-fallbacks' to demonstrate automatic SMS fallback!");

            return;
        }

        event(new ApplianceRateReminderEvent($rate, $overdue));

        $this->info('✓ ApplianceRateReminderEvent fired!');
        $this->info('✓ Standard SMS suppressed (WhatsApp is Primary channel)');
        $this->info("✓ WhatsApp low-balance notification dispatched to {$phone}");
    }

    private function resolveRate(string $reminderKind): ?ApplianceRate {
        $rateId = $this->option('rate');

        if ($rateId) {
            return ApplianceRate::query()->find((int) $rateId);
        }

        $candidates = ApplianceRate::query()
            ->where('remaining', '>', 0)
            ->whereHas('appliancePerson.person.addresses')
            ->whereHas('appliancePerson.appliance.applianceType')
            ->orderBy('due_date')
            ->limit(25)
            ->get();

        $messageService = app(WhatsAppMessageService::class);

        foreach ($candidates as $candidate) {
            $reference = 'appliance_rate:'.$candidate->id.':'.$reminderKind;

            if (!$messageService->hasActiveMessage(WhatsAppMessage::TYPE_LOW_BALANCE_WARNING, $reference)) {
                return $candidate;
            }
        }

        return $candidates->first();
    }
}
