<?php

namespace App\Models\Address;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @template T of Model
 */
interface HasAddressesInterface {
    /**
     * @return MorphMany<Address, T>
     */
    public function addresses(): MorphMany;
}
