<?php

namespace App\Models\Address;

use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

interface HasAddressesInterface {
    public function addresses(): HasOneOrMany;
}
