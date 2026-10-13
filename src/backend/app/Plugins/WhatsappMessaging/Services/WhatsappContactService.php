<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappMessaging\Services;

use App\Models\Address\Address;
use App\Models\Person\Person;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappContactPhoneNotFoundException;
use App\Plugins\WhatsappMessaging\Models\WhatsappConsentLog;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Services\AddressesService;
use App\Services\PersonService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WhatsappContactService {
    public function __construct(
        private WhatsappContact $whatsappContact,
        private AddressesService $addressesService,
        private PersonService $personService,
    ) {}

    public function findOrCreateByPhone(string $e164): WhatsappContact {
        $existingContact = $this->whatsappContact->newQuery()->where('phone', $e164)->first();
        if ($existingContact instanceof WhatsappContact) {
            return $existingContact;
        }

        $address = $this->addressesService->getAddressByPhoneNumber($e164);
        $owner = $address instanceof Address ? $address->owner : null;

        return $this->whatsappContact->newQuery()->create([
            'phone' => $e164,
            'person_id' => $owner instanceof Person ? $owner->id : null,
            'opt_in_status' => WhatsappContact::OPT_IN_STATUS_OPTED_OUT,
        ]);
    }

    public function findOrCreateForPerson(Person $person, ?string $e164): WhatsappContact {
        $phone = $e164 ?? $this->personService->getPrimaryPhoneNumber($person);
        if (in_array($phone, [null, '', '0'], true)) {
            throw new WhatsappContactPhoneNotFoundException('The customer has no primary phone number. Provide a phone number.');
        }

        $contact = $this->findOrCreateByPhone($phone);
        if ($contact->person_id === null) {
            $contact->person_id = $person->id;
            $contact->save();
        }

        return $contact;
    }

    public function findReachable(string $e164): ?WhatsappContact {
        return $this->whatsappContact->newQuery()
            ->where('phone', $e164)
            ->where('opt_in_status', WhatsappContact::OPT_IN_STATUS_OPTED_IN)
            ->first();
    }

    public function optIn(WhatsappContact $contact, string $source, ?int $userId, ?string $rawMessage): bool {
        return $this->changeStatus($contact, WhatsappContact::OPT_IN_STATUS_OPTED_IN, $source, $userId, $rawMessage);
    }

    public function optOut(WhatsappContact $contact, string $source, ?int $userId, ?string $rawMessage): bool {
        return $this->changeStatus($contact, WhatsappContact::OPT_IN_STATUS_OPTED_OUT, $source, $userId, $rawMessage);
    }

    public function recordInbound(WhatsappContact $contact, string $waId, Carbon $receivedAt): void {
        $contact->wa_id = $waId;
        if (!$contact->last_inbound_at instanceof Carbon || $receivedAt->greaterThan($contact->last_inbound_at)) {
            $contact->last_inbound_at = $receivedAt;
        }
        $contact->save();
    }

    public function recordOptInPrompt(WhatsappContact $contact): void {
        $contact->opt_in_prompted_at = Carbon::now();
        $contact->save();
    }

    /**
     * @return LengthAwarePaginator<int, WhatsappContact>
     */
    public function getPaginated(?string $status, int $perPage = 15): LengthAwarePaginator {
        return $this->whatsappContact->newQuery()
            ->with('person')
            ->when($status !== null, fn ($query) => $query->where('opt_in_status', $status))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @return LengthAwarePaginator<int, WhatsappConsentLog>
     */
    public function getConsentLogs(WhatsappContact $contact, int $perPage = 15): LengthAwarePaginator {
        return $contact->consentLogs()->latest()->paginate($perPage);
    }

    private function changeStatus(
        WhatsappContact $contact,
        string $status,
        string $source,
        ?int $userId,
        ?string $rawMessage,
    ): bool {
        if ($contact->opt_in_status === $status) {
            return false;
        }

        $isOptIn = $status === WhatsappContact::OPT_IN_STATUS_OPTED_IN;

        DB::connection('tenant')->transaction(function () use ($contact, $status, $isOptIn, $source, $userId, $rawMessage): void {
            $contact->opt_in_status = $status;
            if ($isOptIn) {
                $contact->opted_in_at = Carbon::now();
            } else {
                $contact->opted_out_at = Carbon::now();
            }
            $contact->save();

            $contact->consentLogs()->create([
                'action' => $isOptIn ? WhatsappConsentLog::ACTION_OPT_IN : WhatsappConsentLog::ACTION_OPT_OUT,
                'source' => $source,
                'user_id' => $userId,
                'raw_message' => $rawMessage,
            ]);
        });

        return true;
    }
}
