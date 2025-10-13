<?php

namespace App\Observers;

use App\Models\Address\Address;

class AddressesObserver {
    /**
     * Handles 'deleted' event of Address.
     */
    public function deleted(Address $address): void {
        // delete the geographic information for that address
        // $address->geo()->delete();
    }
}
