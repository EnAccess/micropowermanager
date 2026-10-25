<?php

namespace App\Plugins\WhatsApp\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Plugins\WhatsApp\Models\WhatsAppMessage;
use App\Plugins\WhatsApp\Services\WhatsAppOptOutService;

class SimulateWebhook extends AbstractSharedCommand {
    protected $signature = 'whatsapp:simulate-webhook
                            {message_id? : Target WhatsApp message ID (e.g. 15 or wamid.xxx); defaults to latest message}
                            {--status=delivered : Simulated provider status (delivered, read, failed, sent)}
                            {--code= : Optional provider error code for failed status (e.g. 131026, 131047)}
                            {--inbound= : Simulate an inbound customer message keyword (e.g. STOP or START)}
                            {--phone= : Recipient phone for inbound message simulation}';

    protected $description = 'Simulate Meta WhatsApp Cloud API webhooks (delivery updates and inbound keywords) without requiring an ngrok tunnel';

    public function handle(WhatsAppOptOutService $optOutService): void {
        // Case 1: Inbound message simulation (e.g. customer texting STOP or START)
        $inbound = $this->option('inbound');
        if ($inbound) {
            $phone = $this->option('phone') ?: '+250791676207';
            $this->info("Simulating inbound WhatsApp message from [{$phone}]: \"{$inbound}\"");
            $response = $optOutService->handleInboundKeyword($phone, (string) $inbound);

            if ($response !== null) {
                $this->info("✓ Inbound keyword recognized!");
                $this->line("Auto-reply sent to customer: \"{$response}\"");
                $this->info("Consent updated: " . ($optOutService->isOptedOut($phone) ? "Opted Out (SMS Fallback active)" : "Opted In (WhatsApp active)"));
            } else {
                $this->line("Inbound message logged (no consent keyword matched).");
            }
            return;
        }

        // Case 2: Outbound delivery status update simulation
        $status = strtolower((string) $this->option('status'));
        $validStatuses = ['sent', 'delivered', 'read', 'failed'];
        if (!in_array($status, $validStatuses, true)) {
            $this->error("Invalid status. Choose from: " . implode(', ', $validStatuses));
            return;
        }

        $targetId = $this->argument('message_id');
        $query = WhatsAppMessage::query();

        if ($targetId) {
            if (is_numeric($targetId)) {
                $message = $query->find((int) $targetId);
            } else {
                $message = $query->where('message_id', $targetId)->first();
            }
        } else {
            $message = $query->latest('id')->first();
        }

        if (!$message instanceof WhatsAppMessage) {
            $this->error('No WhatsApp message found to update.');
            return;
        }

        $now = now();
        $updates = [
            'status' => $status,
            'provider_status_at' => $now,
            'delivery_status_updated_at' => $now,
        ];

        if ($status === 'delivered') {
            $updates['delivered_at'] = $now;
        } elseif ($status === 'read') {
            $updates['delivered_at'] = $updates['delivered_at'] ?? $now;
            $updates['read_at'] = $now;
        } elseif ($status === 'sent') {
            $updates['provider_sent_at'] = $now;
        } elseif ($status === 'failed') {
            $code = $this->option('code') ?: '131026';
            $updates['failed_at'] = $now;
            $updates['provider_error_code'] = (string) $code;
            $updates['error_message'] = "Simulated webhook delivery failure (code {$code})";
        }

        $message->update($updates);
        $message->refresh();

        $this->info("✓ Message [ID: {$message->id}, Type: {$message->notification_type}] updated via simulated webhook to [{$status}]!");

        if ($status === 'failed') {
            $this->warn("Delivery failed! Run 'php artisan whatsapp:send-sms-fallbacks' to trigger automatic SMS fallback.");
        }
    }
}
