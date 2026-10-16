<?php

namespace App\Plugins\WhatsApp\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Plugins\WhatsApp\Models\WhatsAppOptOut;
use App\Plugins\WhatsApp\Services\WhatsAppOptOutService;

class CustomerOptOut extends AbstractSharedCommand {
    protected $signature = 'whatsapp:opt-out
                            {phone : Recipient phone number (e.g. +250791676207)}
                            {--opt-in : Re-subscribe customer to WhatsApp notifications}
                            {--channel=sms : Fallback communication channel preference (sms or none)}
                            {--status : Check customer consent and preferred channel}';

    protected $description = 'Manage customer consent, channel preference (WhatsApp vs SMS), and opt-outs';

    public function handle(WhatsAppOptOutService $optOutService): void {
        $phone = (string) $this->argument('phone');
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        if (empty($cleanPhone)) {
            $this->error('Please provide a valid phone number.');
            return;
        }

        if ($this->option('status')) {
            $optedOut = $optOutService->isOptedOut($cleanPhone);
            $record = WhatsAppOptOut::query()->where('phone', $cleanPhone)->first();

            $this->info("Consent status for [{$phone}]:");
            $this->table(
                ['Field', 'Value'],
                [
                    ['Phone', $cleanPhone],
                    ['Opted Out of WhatsApp?', $optedOut ? 'YES (Opted out)' : 'NO (Consented / Active)'],
                    ['Preferred Channel', $record?->preferred_channel ?? ($optedOut ? 'sms' : 'whatsapp')],
                    ['Opt-Out Reason', $record?->reason ?? 'N/A'],
                    ['Opted Out At', (string) ($record?->opted_out_at ?? 'N/A')],
                ]
            );
            return;
        }

        if ($this->option('opt-in')) {
            $optOutService->optIn($cleanPhone);
            $this->info("✓ Customer [{$phone}] opted in to WhatsApp notifications.");
            $this->info("Primary channel set to: WhatsApp.");
            return;
        }

        $optOutService->optOut($cleanPhone, 'admin_console_request');
        $this->warn("✓ Customer [{$phone}] opted out of WhatsApp.");
        $this->info("Future payment receipts, token deliveries, and reminders will automatically route to SMS.");
    }
}
