<?php

namespace App\Plugins\WhatsappMessaging\Services;

use App\Models\Person\Person;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappContactPhoneNotFoundException;
use App\Plugins\WhatsappMessaging\Helpers\WhatsappPhoneNumber;
use App\Plugins\WhatsappMessaging\Models\WhatsappConsentLog;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Services\AddressesService;
use App\Services\PersonService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WhatsappContactService {
    public function __construct(private WhatsappContact $whatsappContact, private AddressesService $addressesService, private PersonService $personService) {}

    public function receiveFrom(string $waId, int $timestamp): WhatsappContact {
        $contact = $this->findOrCreate(WhatsappPhoneNumber::fromWaId($waId));
        // A resent older message must not move the customer service window backwards.
        $contact->update(['wa_id' => $waId, 'last_inbound_at' => max($contact->last_inbound_at, Carbon::createFromTimestamp($timestamp))]);

        return $contact;
    }

    public function findOrCreateForPerson(Person $person, ?string $e164): WhatsappContact {
        $phone = $e164 ?? $this->personService->getPrimaryPhoneNumber($person);
        if (in_array($phone, [null, ''], true)) {
            throw new WhatsappContactPhoneNotFoundException('The customer has no primary phone number. Provide a phone number.');
        }
        $contact = $this->findOrCreate($phone);
        $contact->update(['person_id' => $contact->person_id ?? $person->id]);

        return $contact;
    }

    public function findReachable(string $e164): ?WhatsappContact {
        return $this->whatsappContact->newQuery()->where('phone', $e164)->where('opt_in_status', WhatsappContact::OPT_IN_STATUS_OPTED_IN)->first();
    }

    /** @return bool false when the contact already had that status, so a resent webhook changes and logs nothing */
    public function changeConsent(WhatsappContact $contact, string $action, string $source, ?int $userId, ?string $rawMessage): bool {
        $status = $action === WhatsappConsentLog::ACTION_OPT_IN ? WhatsappContact::OPT_IN_STATUS_OPTED_IN : WhatsappContact::OPT_IN_STATUS_OPTED_OUT;
        if ($contact->opt_in_status === $status) {
            return false;
        }
        DB::connection('tenant')->transaction(function () use ($contact, $status, $action, $source, $userId, $rawMessage): void {
            $contact->update(['opt_in_status' => $status, $status === WhatsappContact::OPT_IN_STATUS_OPTED_IN ? 'opted_in_at' : 'opted_out_at' => now()]);
            $contact->consentLogs()->create(['action' => $action, 'source' => $source, 'user_id' => $userId, 'raw_message' => $rawMessage]);
        });

        return true;
    }

    private function findOrCreate(string $e164): WhatsappContact {
        $owner = $this->addressesService->getAddressByPhoneNumber($e164)?->owner;

        return $this->whatsappContact->newQuery()->firstOrCreate(['phone' => $e164], ['person_id' => $owner instanceof Person ? $owner->id : null, 'opt_in_status' => WhatsappContact::OPT_IN_STATUS_OPTED_OUT]);
    }
}
