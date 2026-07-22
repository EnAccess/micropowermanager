<?php

namespace App\Observers;

use App\Models\Person\Person;

class PersonObserver {
    /**
     * Handle the Person "updated" event.
     */
    public function updated(Person $person): void {}

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(Person $person): void {
        /*
        in order to fire the deleted event on relation models,
        the model should be pulled first and deleted afterwards.
        */

        // delete all addresses
        foreach ($person->addresses()->get() as $address) {
            $address->delete();
        }

        // Detach devices instead of deleting them so the hardware (and its
        // underlying meter/SHS) can be reassigned to another customer.
        $person->devices()->update(['person_id' => null]);

        // delete all transactions which are belong to that person
        foreach ($person->payments()->get() as $transaction) {
            $transaction->delete();
        }
    }
}
