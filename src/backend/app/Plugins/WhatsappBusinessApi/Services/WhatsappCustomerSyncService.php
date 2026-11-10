<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappBusinessApi\Services;

use App\Models\Address\Address;
use App\Models\MpmPlugin;
use App\Models\Person\Person;
use App\Models\Sms;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappBusinessOptIn;
use App\Services\AddressesService;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

class WhatsappCustomerSyncService {
    public function __construct(
        private SmsService $smsService,
        private AddressesService $addressesService,
    ) {}

    /**
     * Parse an inbound message body for phone number change commands.
     * Supported patterns:
     * - UPDATE NUMBER +255788123456
     * - CHANGE NUMBER +255788123456
     * - UPDATE PHONE +255788123456
     * - CHANGE PHONE +255788123456
     * - NEW NUMBER +255788123456
     * - BADILI NAMBA +255788123456 (Swahili).
     */
    public function parseNumberChangeCommand(string $body): ?string {
        $patterns = [
            '/^(?:update|change|new|badili)\s+(?:number|phone|namba)\s*:?\s*(\+?[0-9]{7,15})/i',
            '/^(?:update|change)\s+(\+?[0-9]{7,15})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, trim($body), $matches)) {
                return $this->normalizePhone($matches[1]);
            }
        }

        return null;
    }

    public function parseOptInCommand(string $body): bool {
        return (bool) preg_match('/^(?:start|optin|opt-in|opt\s+in|subscribe|yes|ndio|anza|jiunge)$/i', trim($body));
    }

    public function parseOptOutCommand(string $body): bool {
        return (bool) preg_match('/^(?:stop|optout|opt-out|opt\s+out|unsubscribe|cancel|acha|sitaki)$/i', trim($body));
    }

    public function ensureCustomerOptedIn(Person $person): void {
        $optIn = WhatsappBusinessOptIn::query()->where('person_id', $person->id)->first();
        if (!$optIn) {
            WhatsappBusinessOptIn::query()->create([
                'person_id' => $person->id,
                'opted_in' => true,
            ]);
            Log::info('Automatically recorded conversational opt-in for customer', [
                'person_id' => $person->id,
            ]);
        }
    }

    /**
     * Synchronize a customer's phone number update in MPM.
     * Updates primary address phone, syncs WhatsApp opt-in, and logs audit record.
     */
    public function syncCustomerPhoneNumber(Person $person, string $newPhone, string $source = 'whatsapp'): Address {
        $normalizedPhone = $this->normalizePhone($newPhone);

        // 1. Locate primary address or first address for this person
        /** @var Address|null $address */
        $address = Address::query()
            ->where('owner_id', $person->id)
            ->where(function ($q): void {
                $q->where('owner_type', 'person')
                    ->orWhere('owner_type', Person::class);
            })
            ->where('is_primary', 1)
            ->first()
            ?? Address::query()
                ->where('owner_id', $person->id)
                ->where(function ($q): void {
                    $q->where('owner_type', 'person')
                        ->orWhere('owner_type', Person::class);
                })
                ->first();

        $oldPhone = $address ? (string) $address->phone : null;

        if ($address instanceof Address) {
            $address->update(['phone' => $normalizedPhone]);
        } else {
            /** @var Address $address */
            $address = new Address([
                'phone' => $normalizedPhone,
                'is_primary' => 1,
            ]);
            $address->owner()->associate($person);
            $address->save();
        }

        // 2. Synchronize WhatsApp Opt-In for this person
        WhatsappBusinessOptIn::query()->updateOrCreate(
            ['person_id' => $person->id],
            ['opted_in' => true]
        );

        Log::info('Customer phone number synchronized in MPM', [
            'person_id' => $person->id,
            'old_phone' => $oldPhone,
            'new_phone' => $normalizedPhone,
            'source' => $source,
        ]);

        return $address;
    }

    /**
     * Handle incoming message when an existing customer requests a number update.
     */
    public function handleInboundNumberChange(string $fromPhone, string $body): ?Address {
        $newPhone = $this->parseNumberChangeCommand($body);
        if (!$newPhone) {
            return null;
        }

        $person = $this->findPersonByPhone($fromPhone);
        if (!$person) {
            Log::warning('Received number change command from unknown phone', [
                'from' => $fromPhone,
                'requested_new_phone' => $newPhone,
            ]);

            return null;
        }

        $address = $this->syncCustomerPhoneNumber($person, $newPhone, 'inbound_message');

        // Automatically dispatch confirmation message to the customer
        $this->sendConfirmationMessage($newPhone, "Your phone number in MicroPowerManager has been successfully updated to {$newPhone}.");

        return $address;
    }

    /**
     * Handle Meta system webhook event when a customer changes their WhatsApp number.
     * Meta sends: messages[type='system'], system.customer / system.new_wa_id.
     */
    public function handleSystemNumberChange(string $fromPhone, string $newWaId): ?Address {
        $person = $this->findPersonByPhone($fromPhone);
        if (!$person) {
            Log::warning('WhatsApp system number change received for unassociated customer', [
                'from_phone' => $fromPhone,
                'new_wa_id' => $newWaId,
            ]);

            return null;
        }

        $normalizedNewPhone = $this->normalizePhone($newWaId);
        $address = $this->syncCustomerPhoneNumber($person, $normalizedNewPhone, 'system_webhook');

        Log::info('Successfully processed WhatsApp system number change', [
            'person_id' => $person->id,
            'old_phone' => $fromPhone,
            'new_phone' => $normalizedNewPhone,
        ]);

        return $address;
    }

    public function findPersonByPhone(string $phone): ?Person {
        $address = $this->addressesService->getAddressByPhoneNumber($phone);
        if ($address instanceof Address && $address->owner instanceof Person) {
            return $address->owner;
        }

        $clean = preg_replace('/[^0-9]/', '', $phone) ?? '';
        $normalized = '+'.$clean;
        $candidates = array_unique(array_filter([
            $normalized,
            $phone,
            $clean,
            ltrim($clean, '0'),
            '0'.ltrim($clean, '0'),
        ]));

        /** @var Address|null $address */
        $address = Address::query()
            ->whereIn('owner_type', ['person', Person::class])
            ->where(function ($query) use ($candidates, $clean): void {
                $query->whereIn('phone', $candidates);
                if ($clean !== '') {
                    $query->orWhereRaw("REGEXP_REPLACE(phone, '[^0-9]', '') = ?", [$clean]);
                }
            })
            ->first();

        if (!$address || !$address->owner_id) {
            return null;
        }

        /** @var Person|null $person */
        return Person::query()->find($address->owner_id);
    }

    public function normalizePhone(string $phone): string {
        $clean = preg_replace('/[^0-9]/', '', $phone) ?? '';

        return '+'.$clean;
    }

    private function sendConfirmationMessage(string $receiver, string $message): void {
        try {
            $smsData = [
                'receiver' => $receiver,
                'body' => $message,
                'direction' => Sms::DIRECTION_OUTGOING,
                'status' => Sms::STATUS_STORED,
                'gateway_id' => MpmPlugin::WHATSAPP_BUSINESS_API,
            ];
            $this->smsService->createAndSendSms($smsData);
        } catch (\Throwable $e) {
            Log::warning('Failed to dispatch phone change confirmation message', [
                'receiver' => $receiver,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
