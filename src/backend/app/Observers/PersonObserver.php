<?php

namespace App\Observers;

use App\Models\Meter\Meter;
use App\Models\Person\Person;
use Illuminate\Support\Facades\Log;

class PersonObserver {
    /**
     * Handle the Person "updated" event.
     */
    public function updated(Person $person): void {
        Log::debug($person->id.'updated');
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(Person $person): void {
        // delete all associated roles
        $person->roleOwner()->get();

        /*
        in order to fire the deleted event on relation models,
        the model should be pulled first and deleted afterwards.
        */

        // delete all addresses
        foreach ($person->addresses()->get() as $address) {
            $address->delete();
        }
        foreach ($person->devices()->get() as $device) {
            if ($device->device instanceof Meter) {
                $device->device->delete();
            }
            $device->delete();
        }

        // delete all transactions which are belong to that person
        foreach ($person->payments()->get() as $transaction) {
            $transaction->delete();
        }
    }
}
