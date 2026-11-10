<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappBusinessApi\Services;

use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterConsumption;
use App\Models\MpmPlugin;
use App\Models\Person\Person;
use App\Models\Sms;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketCategory;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use App\Plugins\WhatsappBusinessApi\WhatsappGateway;

/**
 * Handles automated WhatsApp self-service menus and button actions for customers.
 *
 * Provides instant self-service options:
 * 1. Get latest token
 * 2. Did not receive token
 * 3. Status for low balance
 * 4. Meter info
 */
class WhatsappSelfService {
    public const string ACTION_LATEST_TOKEN = 'btn_latest_token';
    public const string ACTION_NOT_RECEIVED_TOKEN = 'btn_not_received_token';
    public const string ACTION_LOW_BALANCE = 'btn_low_balance';
    public const string ACTION_METER_INFO = 'btn_meter_info';

    public function __construct(
        private WhatsappGateway $gateway,
    ) {}

    /**
     * Resolves incoming text or interactive button ID to a supported self-service action.
     */
    public function resolveAction(string $body, ?string $interactiveId = null): ?string {
        if ($interactiveId !== null && $interactiveId !== '') {
            return match ($interactiveId) {
                self::ACTION_LATEST_TOKEN => self::ACTION_LATEST_TOKEN,
                self::ACTION_NOT_RECEIVED_TOKEN => self::ACTION_NOT_RECEIVED_TOKEN,
                self::ACTION_LOW_BALANCE => self::ACTION_LOW_BALANCE,
                self::ACTION_METER_INFO => self::ACTION_METER_INFO,
                default => null,
            };
        }

        $normalized = mb_strtolower(trim($body));
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', '', $normalized) ?? $normalized;

        if (preg_match('/^(to\s+)?get\s+latest\s+token|latest\s+token|my\s+token|^token$|^1$/i', $normalized) === 1) {
            return self::ACTION_LATEST_TOKEN;
        }

        if (preg_match('/did\s+not\s+(recieve|receive)\s+token|not\s+(received|recieved)\s+token|missing\s+token|no\s+token|havent\s+received\s+token|^2$/i', $normalized) === 1) {
            return self::ACTION_NOT_RECEIVED_TOKEN;
        }

        if (preg_match('/status\s+(for\s+)?low\s+balance|low\s+balance|balance\s+status|check\s+balance|^balance$|^credit$|^3$/i', $normalized) === 1) {
            return self::ACTION_LOW_BALANCE;
        }

        if (preg_match('/meter\s+info|meter\s+information|meter\s+details|my\s+meter|^meter$|^info$|^4$/i', $normalized) === 1) {
            return self::ACTION_METER_INFO;
        }

        return null;
    }

    /**
     * Dispatches the interactive self-service page containing the 4 buttons to the customer.
     */
    public function sendMenu(string $receiver, ?Person $person = null, ?string $phoneNumberId = null): ?string {
        $bodyText = 'Hello! Please select an option from the menu below to manage your energy service:';
        $headerText = 'Self-Service Menu';
        $footerText = 'MicroPowerManager';
        $buttonText = 'Select Option';

        $options = [
            [
                'id' => self::ACTION_LATEST_TOKEN,
                'title' => 'Get latest token',
                'description' => 'View your most recent energy token',
            ],
            [
                'id' => self::ACTION_NOT_RECEIVED_TOKEN,
                'title' => 'Did not receive token',
                'description' => 'Report missing token or check payment',
            ],
            [
                'id' => self::ACTION_LOW_BALANCE,
                'title' => 'Status for low balance',
                'description' => 'Check current balance and credit',
            ],
            [
                'id' => self::ACTION_METER_INFO,
                'title' => 'Meter info',
                'description' => 'View registered meter and tariff',
            ],
        ];

        $wamid = $this->gateway->sendInteractiveMenu(
            receiver: $receiver,
            bodyText: $bodyText,
            options: $options,
            buttonText: $buttonText,
            headerText: $headerText,
            footerText: $footerText,
            phoneNumberId: $phoneNumberId,
        );

        $summaryText = "MicroPowerManager Self-Service Menu:\n1. Get latest token\n2. Did not receive token\n3. Status for low balance\n4. Meter info";

        Sms::query()->create([
            'receiver' => $receiver,
            'body' => $summaryText,
            'sender_id' => $person?->id,
            'direction' => Sms::DIRECTION_OUTGOING,
            'status' => Sms::STATUS_DELIVERED,
            'gateway_id' => MpmPlugin::WHATSAPP_BUSINESS_API,
            'uuid' => $wamid ? substr((string) $wamid, 0, 191) : null,
        ]);

        return $wamid;
    }

    /**
     * Executes the self-service action, delivers the result to the customer over WhatsApp,
     * and logs the outgoing message in the SMS conversations table.
     */
    public function handleAction(string $action, string $receiver, ?Person $person, ?Address $address = null, ?string $phoneNumberId = null): string {
        try {
            $responseText = match ($action) {
                self::ACTION_LATEST_TOKEN => $this->handleLatestToken($receiver, $person),
                self::ACTION_NOT_RECEIVED_TOKEN => $this->handleNotReceivedToken($receiver, $person),
                self::ACTION_LOW_BALANCE => $this->handleLowBalance($receiver, $person),
                self::ACTION_METER_INFO => $this->handleMeterInfo($receiver, $person),
                default => 'Invalid option selected. Please select an option from the menu.',
            };
        } catch (\Throwable $e) {
            Log::error('Self-service action execution failed', [
                'action' => $action,
                'receiver' => $receiver,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $responseText = 'Sorry, we encountered an issue retrieving your information. Please contact customer care.';
        }

        $wamid = $this->gateway->sendTextMessage($receiver, $responseText, $phoneNumberId);

        Sms::query()->create([
            'receiver' => $receiver,
            'body' => $responseText,
            'sender_id' => $person?->id,
            'direction' => Sms::DIRECTION_OUTGOING,
            'status' => Sms::STATUS_DELIVERED,
            'gateway_id' => MpmPlugin::WHATSAPP_BUSINESS_API,
            'uuid' => $wamid ? substr((string) $wamid, 0, 191) : null,
        ]);

        return $responseText;
    }

    private function handleLatestToken(string $receiver, ?Person $person): string {
        if (!$person instanceof Person) {
            return "No customer account found for phone number {$receiver}. Please contact customer care to register.";
        }

        $deviceIds = $person->devices()->pluck('id');
        $token = Token::query()->whereIn('device_id', $deviceIds)->latest('id')->first();

        if (!$token instanceof Token) {
            $deviceSerials = $person->devices()->pluck('device_serial')->filter()->all();
            $token = Token::query()->whereHas('transaction', function ($q) use ($receiver, $deviceSerials): void {
                $q->where('sender', $receiver);
                if (!empty($deviceSerials)) {
                    $q->orWhereIn('message', $deviceSerials);
                }
            })->latest('id')->first();
        }

        if (!$token instanceof Token) {
            return "No previous tokens found for your account. If you recently made a payment, please select 'Did not receive token' or allow a moment for confirmation.";
        }

        $meterSerial = $token->device?->device_serial ?? 'N/A';
        $amount = $token->token_amount ? "{$token->token_amount} ".($token->token_unit ?? 'kWh') : 'N/A';
        $date = $token->created_at ? $token->created_at->format('Y-m-d H:i') : 'Recent';

        return "⚡ *Latest Token Details*:\n• Token: {$token->token}\n• Units: {$amount}\n• Meter: {$meterSerial}\n• Date: {$date}\n\nPlease enter this token code into your meter keypad.";
    }

    private function handleNotReceivedToken(string $receiver, ?Person $person): string {
        $deviceSerials = $person ? $person->devices()->pluck('device_serial')->filter()->all() : [];

        $recentTransaction = Transaction::query()
            ->where(function ($q) use ($receiver, $deviceSerials): void {
                $q->where('sender', $receiver);
                if (!empty($deviceSerials)) {
                    $q->orWhereIn('message', $deviceSerials);
                }
            })
            ->latest('id')
            ->first();

        if ($recentTransaction instanceof Transaction && $recentTransaction->token instanceof Token) {
            $token = $recentTransaction->token;
            $meterSerial = $token->device?->device_serial ?? 'N/A';
            $date = $recentTransaction->created_at ? $recentTransaction->created_at->format('Y-m-d H:i') : 'Recent';

            return "We located your recent payment on {$date}.\nHere is your token:\n• Token: {$token->token}\n• Units: {$token->token_amount} {$token->token_unit}\n• Meter: {$meterSerial}";
        }

        // Open an escalated support ticket in MPM
        $category = TicketCategory::query()->first();
        $categoryId = $category?->id ?? 1;

        $ticket = Ticket::query()->create([
            'title' => 'Missing Token Report: '.$receiver,
            'content' => 'Customer reported missing token via WhatsApp Self-Service.'
                .($recentTransaction ? "\nLast Transaction ID: {$recentTransaction->id}, Amount: {$recentTransaction->amount}" : "\nNo recent transaction found in system.")
                ."\nCustomer Phone: {$receiver}",
            'category_id' => $categoryId,
            'status' => Ticket::STATUS['opened'],
            'owner_type' => $person ? $person->getMorphClass() : 'person',
            'owner_id' => $person?->id ?? 0,
            'creator_type' => $person ? $person->getMorphClass() : 'person',
            'creator_id' => $person?->id ?? 0,
        ]);

        return "⚠️ *Missing Token Ticket Created (#{$ticket->id})*\nWe have logged your report with our support desk. Our technical team has been alerted and is verifying your transaction to deliver your token promptly.";
    }

    private function handleLowBalance(string $receiver, ?Person $person): string {
        if (!$person instanceof Person) {
            return "No meter registered to this phone number ({$receiver}).";
        }

        $devices = $person->devices()->with('device')->get();
        $meters = $devices->map(fn ($d) => $d->device)->filter(fn ($m) => $m instanceof Meter);

        if ($meters->isEmpty()) {
            return 'No active meter found linked to your account.';
        }

        /** @var Meter $meter */
        $meter = $meters->first();
        $consumption = MeterConsumption::query()->where('meter_id', $meter->id)->latest('reading_date')->first();
        $credit = $consumption ? (float) $consumption->credit_on_meter : null;

        if ($credit !== null) {
            if ($credit <= 5.0) {
                return "⚠️ *Status for Low Balance*:\n• Meter: {$meter->serial_number}\n• Remaining Credit: {$credit} kWh\n⚠️ Warning: Your credit is low! Please recharge soon to prevent power interruption.";
            }

            return "✅ *Status for Low Balance*:\n• Meter: {$meter->serial_number}\n• Remaining Credit: {$credit} kWh\nYour balance is sufficient and your electricity connection is active.";
        }

        return "ℹ️ *Balance Status*:\n• Meter: {$meter->serial_number}\nLive credit balance is currently being synced from the minigrid. Please check back shortly.";
    }

    private function handleMeterInfo(string $receiver, ?Person $person): string {
        if (!$person instanceof Person) {
            return "No meter registered to phone number {$receiver}. Please contact customer care.";
        }

        $devices = $person->devices()->with('device')->get();
        $meters = $devices->map(fn ($d) => $d->device)->filter(fn ($m) => $m instanceof Meter);

        if ($meters->isEmpty()) {
            $otherDevices = $devices->map(fn ($d) => $d->device)->filter();
            if ($otherDevices->isNotEmpty()) {
                $lines = [];
                foreach ($otherDevices as $d) {
                    $serial = $d->serial_number ?? $d->device_serial ?? 'N/A';
                    $type = class_basename($d);
                    $lines[] = "• {$type}: {$serial}";
                }

                return "📋 *Registered Device Information*:\nCustomer: {$person->name} {$person->surname}\n\n".implode("\n", $lines);
            }

            return 'No active meter found registered to your account.';
        }

        $meters->loadMissing(['meterType', 'tariff', 'connectionType']);

        $infoLines = [];
        foreach ($meters as $m) {
            /** @var Meter $m */
            $typeName = $m->meterType?->name ?? 'Standard Prepaid';
            $tariffName = $m->tariff?->name ?? 'Standard Tariff';
            $connName = $m->connectionType?->name ?? 'Single Phase';
            $status = $m->in_use ? 'Active' : 'Inactive';
            $infoLines[] = "• Meter Number: {$m->serial_number}\n  Type: {$typeName}\n  Tariff: {$tariffName}\n  Connection: {$connName}\n  Status: {$status}";
        }

        $meterText = implode("\n\n", $infoLines);

        return "📋 *Meter Information*:\nCustomer: {$person->name} {$person->surname}\n\n{$meterText}";
    }
}
