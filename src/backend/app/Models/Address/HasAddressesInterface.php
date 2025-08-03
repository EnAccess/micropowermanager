<?php

namespace App\Models\Address;

use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

interface HasAddressesInterface {
    /**
     * @return HasOneOrMany<Address, Model, Collection<int, Address>>
     */
    public function addresses(): HasOneOrMany;
}
