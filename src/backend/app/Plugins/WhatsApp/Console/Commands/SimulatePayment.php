<?php

namespace App\Plugins\WhatsApp\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Events\TransactionSuccessfulEvent;
use App\Models\Device;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use App\Plugins\WhatsApp\Models\WhatsAppMessage;

class SimulatePayment extends AbstractSharedCommand {
    protected $signature = 'whatsapp:simulate-payment
                            {phone? : Recipient phone number (e.g. +250791676207)}
                            {--amount=5000 : Payment amount in TZS}
                            {--token= : Energy meter token}
                            {--fail : Simulate WhatsApp failure to demonstrate SMS fallback}';

    protected $description = 'Simulate customer energy payment for demo: WhatsApp primary & SMS fallback';

    public function handle(): void {
        $phone = $this->argument('phone') ?: '+250791676207';
        $amount = (float) $this->option('amount');
        $tokenCode = $this->option('token') ?: sprintf('%04d-%04d-%04d', rand(1000, 9999), rand(1000, 9999), rand(1000, 9999));
        $shouldFail = (bool) $this->option('fail');

        $this->info("Simulating energy payment of {$amount} TZS for customer {$phone}...");

        // Linking a real device serial resolves the demo customer so the
        // notification greets them by name.
        $deviceSerial = Device::query()
            ->whereNotNull('person_id')
            ->orderBy('id')
            ->value('device_serial')
            ?? 'DEMO-'.rand(100000, 999999);

        $transaction = Transaction::query()->create([
            'amount' => $amount,
            'sender' => $phone,
            'message' => $deviceSerial,
            'original_transaction_id' => (string) rand(1000000, 9999999),
            'original_transaction_type' => 'cash_transaction',
        ]);

        $token = Token::query()->create([
            'transaction_id' => $transaction->id,
            'token' => $tokenCode,
            'token_type' => Token::TYPE_ENERGY,
            'token_unit' => Token::UNIT_KWH,
            'token_amount' => round($amount / 300, 1),
        ]);

        $this->info("✓ Transaction created [ID: {$transaction->id}] with Token [{$tokenCode}]");

        if ($shouldFail) {
            $this->warn("Simulating WhatsApp delivery failure...");
            $message = WhatsAppMessage::query()->create([
                'notification_type' => WhatsAppMessage::TYPE_TOKEN_DELIVERY,
                'recipient_phone' => $phone,
                'recipient_name' => 'Demo Customer',
                'body' => "Hello Demo Customer, your token is {$tokenCode}. Thank you for your payment.",
                'transaction_id' => $transaction->id,
                'reference' => 'transaction:'.$transaction->id,
                'status' => WhatsAppMessage::STATUS_FAILED,
                'error_message' => 'Simulated WhatsApp delivery failure (network timeout / recipient unreachable)',
            ]);
            $this->error("✗ WhatsApp delivery failed [Message ID: {$message->id}]");
            $this->line("Run 'php artisan whatsapp:send-sms-fallbacks' to demonstrate automatic SMS fallback!");
            return;
        }

        // Fire TransactionSuccessfulEvent -> triggers WhatsApp as primary channel
        event(new TransactionSuccessfulEvent($transaction));

        $this->info("✓ TransactionSuccessfulEvent fired!");
        $this->info("✓ Standard SMS suppressed (WhatsApp is Primary channel)");
        $this->info("✓ WhatsApp token notification dispatched to {$phone}");
    }
}
