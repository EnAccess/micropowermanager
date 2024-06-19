<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 13.07.18
 * Time: 11:09.
 */

namespace App\Models\Address;

use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

interface HasAddressesInterface
{
    public function addresses(): HasOneOrMany;
}
