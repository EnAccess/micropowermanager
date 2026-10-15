<?php

namespace App\Plugins\WhatsappMessaging\Services;

use App\Models\ApplianceRate;
use App\Models\Device;
use App\Models\MainSettings;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use Illuminate\Support\Carbon;

class WhatsappSelfServiceService {
    private const string HELP = "Send one of these commands:\nTOKEN - your last token\nTOKEN <serial> - the last token of one device\nBALANCE - your outstanding appliance balance\nReply STOP to unsubscribe.";

    public function __construct(private MainSettings $mainSettings, private ApplianceRate $applianceRate) {}

    /** The customer is identified only by the number they write from, so every answer is limited to that person's own devices and appliances. */
    public function reply(WhatsappContact $contact, string $body): string {
        $settings = $this->mainSettings->newQuery()->first();
        if (!$contact->person instanceof Person) {
            return sprintf('This number is not linked to a customer account. Please contact %s.', $settings->company_name ?? 'us');
        }
        // Only the command is case-insensitive; serial numbers are matched exactly as typed.
        [$command, $serial] = array_pad(preg_split('/\s+/', trim($body), 2) ?: [], 2, '');

        return match (mb_strtoupper($command)) {
            'TOKEN' => $this->token($contact->person, $serial, $settings->currency ?? ''),
            'BALANCE' => $this->balance($contact->person, $settings->currency ?? ''),
            default => self::HELP,
        };
    }

    private function token(Person $person, string $serial, string $currency): string {
        $devices = $person->devices()->orderBy('device_serial')->get();
        // Someone else's serial gets the same answer as an unknown one, so the reply never reveals that a device exists.
        $device = $serial !== '' ? $devices->firstWhere('device_serial', $serial) : ($devices->count() === 1 ? $devices->first() : null);
        if (!$device instanceof Device) {
            return match (true) {
                $serial !== '' => sprintf('No device with serial %s was found on your account.', $serial),
                $devices->isEmpty() => 'You have no devices registered.',
                default => sprintf("You have several devices:\n%s\nSend TOKEN <serial> for one of them.", $devices->map(fn (Device $device): string => '- '.$device->device_serial)->implode("\n")),
            };
        }
        $transaction = $device->transactions()->whereHas('token')->with('token')->latest('id')->first();

        return $transaction instanceof Transaction
            ? sprintf('Last payment for %s: %s %s on %s. %s', $device->device_serial, $transaction->amount, $currency, $transaction->created_at?->format('Y-m-d') ?? '-', $transaction->token)
            : sprintf('No payment with a token was found for %s.', $device->device_serial);
    }

    private function balance(Person $person, string $currency): string {
        $openRates = $this->applianceRate->newQuery()->whereIn('appliance_person_id', $person->appliancePerson()->select('id'))->where('remaining', '>', 0)->oldest('due_date')->get();

        // ApplianceRate does not cast due_date, so it arrives as the raw database string.
        return $openRates->isEmpty()
            ? 'You have no outstanding appliance balance.'
            : sprintf('Your outstanding appliance balance is %s %s. Next payment: %s %s due on %s.', $openRates->sum('remaining'), $currency, $openRates->first()->remaining, $currency, Carbon::parse($openRates->first()->due_date)->toDateString());
    }
}
