<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappBusinessApi\Observers;

use App\Models\Address\Address;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappBusinessOptIn;
use Illuminate\Support\Facades\Log;

class AddressObserver {
    public function updated(Address $address): void {
        if (in_array($address->owner_type, ['person', \App\Models\Person\Person::class], true) && $address->wasChanged('phone') && !empty($address->owner_id)) {
            Log::info('Customer address phone changed in MPM. Synchronizing WhatsApp opt-in records.', [
                'person_id' => $address->owner_id,
                'new_phone' => $address->phone,
            ]);

            // Preserve explicit opt-out status if already configured; create opt-in if none existed
            $optIn = WhatsappBusinessOptIn::query()->where('person_id', $address->owner_id)->first();
            if ($optIn) {
                $optIn->touch();
            } else {
                WhatsappBusinessOptIn::query()->create([
                    'person_id' => $address->owner_id,
                    'opted_in' => true,
                ]);
            }
        }
    }
}
