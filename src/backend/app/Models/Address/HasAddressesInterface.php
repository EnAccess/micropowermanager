<?php

namespace App\Models\Address;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface HasAddressesInterface {
    // Laravel relations return templates of the special type
    // $this(TDeclaringModel). It seems impossible to write a
    // non-generic return type at interface level that fullfils
    // this behaviour.
    // @phpstan-ignore missingType.generics
    public function addresses(): MorphMany;
}
