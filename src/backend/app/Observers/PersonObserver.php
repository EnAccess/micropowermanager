<?php

namespace App\Observers;

use App\Models\Person\Person;
use Illuminate\Support\Facades\Log;

class PersonObserver {
    /**
     * Handle the Person "updated" event.
     *
     * @param Person $person
     *
     * @return void
     */
    public function updated(Person $person): void {
        Log::debug($person->id.'updated');
    }

    /**
     * Handle the User "deleted" event.
     *
     * @param Person $person
     *
     * @return void
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
            if ($device->device_type === 'meter' && $device->device !== null) {
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
