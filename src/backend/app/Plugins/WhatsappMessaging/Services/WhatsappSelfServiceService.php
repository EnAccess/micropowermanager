<?php

namespace App\Plugins\WhatsappMessaging\Services;

use App\Models\ApplianceRate;
use App\Models\Device;
use App\Models\MainSettings;
use App\Models\Person\Person;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use Illuminate\Support\Carbon;

class WhatsappSelfServiceService {
    private const string COMMAND_TOKEN = 'TOKEN';
    private const string COMMAND_BALANCE = 'BALANCE';

    public function __construct(
        private MainSettings $mainSettings,
        private ApplianceRate $applianceRate,
    ) {}

    /**
     * The customer is identified only by the WhatsApp number they write from, so every answer is limited to that person's own devices and appliances.
     */
    public function reply(WhatsappContact $contact, string $body): string {
        $person = $contact->person;

        if (!$person instanceof Person) {
            return sprintf('This number is not linked to a customer account. Please contact %s.', $this->settings()->company_name ?? 'us');
        }

        $words = preg_split('/\s+/', trim($body), 2) ?: [''];
        // Only the command is case-insensitive; serial numbers are matched exactly as typed.
        $command = mb_strtoupper($words[0]);
        $serial = isset($words[1]) ? trim($words[1]) : null;

        return match ($command) {
            self::COMMAND_TOKEN => $this->tokenReply($person, $serial),
            self::COMMAND_BALANCE => $this->balanceReply($person),
            default => $this->helpReply(),
        };
    }

    private function tokenReply(Person $person, ?string $serial): string {
        $devices = $person->devices()->orderBy('device_serial')->get();

        if ($serial !== null && $serial !== '') {
            $device = $devices->firstWhere('device_serial', $serial);

            // Someone else's serial gets the same answer as an unknown one, so the reply never reveals that a device exists.
            return $device instanceof Device
                ? $this->lastTokenReply($device)
                : sprintf('No device with serial %s was found on your account.', $serial);
        }

        if ($devices->isEmpty()) {
            return 'You have no devices registered.';
        }

        if ($devices->count() > 1) {
            return sprintf(
                "You have several devices:\n%s\nSend TOKEN <serial> for one of them.",
                $devices->pluck('device_serial')->map(fn (string $deviceSerial): string => '- '.$deviceSerial)->implode("\n"),
            );
        }

        return $this->lastTokenReply($devices->first());
    }

    private function lastTokenReply(Device $device): string {
        $transaction = $device->transactions()->whereHas('token')->with('token')->latest('id')->first();

        if (!$transaction instanceof Transaction || !$transaction->token instanceof Token) {
            return sprintf('No payment with a token was found for %s.', $device->device_serial);
        }

        return sprintf(
            'Last payment for %s: %s %s on %s. %s',
            $device->device_serial,
            $transaction->amount,
            $this->settings()->currency ?? '',
            $transaction->created_at?->format('Y-m-d') ?? '-',
            (string) $transaction->token,
        );
    }

    private function balanceReply(Person $person): string {
        $openRates = $this->applianceRate->newQuery()
            ->whereIn('appliance_person_id', $person->appliancePerson()->select('id'))
            ->where('remaining', '>', 0)
            ->oldest('due_date')
            ->get();

        if ($openRates->isEmpty()) {
            return 'You have no outstanding appliance balance.';
        }

        $currency = $this->settings()->currency ?? '';
        $nextRate = $openRates->first();

        return sprintf(
            'Your outstanding appliance balance is %s %s. Next payment: %s %s due on %s.',
            $openRates->sum('remaining'),
            $currency,
            $nextRate->remaining,
            $currency,
            // ApplianceRate does not cast due_date, so it arrives as the raw database string.
            Carbon::parse($nextRate->due_date)->toDateString(),
        );
    }

    private function helpReply(): string {
        return "Send one of these commands:\n"
            ."TOKEN - your last token\n"
            ."TOKEN <serial> - the last token of one device\n"
            ."BALANCE - your outstanding appliance balance\n"
            .'Reply STOP to unsubscribe.';
    }

    private function settings(): ?MainSettings {
        return $this->mainSettings->newQuery()->first();
    }
}
