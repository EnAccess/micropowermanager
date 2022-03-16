<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 31.07.18
 * Time: 22:08
 */

namespace App\Observers;

use App\Models\Address\Address;

class AddressesObserver
{
    /**
     * Handles 'deleted' event of Address
     *
     * @param  Address $address
     * @return void
     */
    public function deleted(Address $address): void
    {
        //delete the geographic information for that address
        //$address->geo()->delete();
    }
}
