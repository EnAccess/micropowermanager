<?php

namespace App\Models\Meter;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeterType extends BaseModel
{
    public static $rules = [
        'online' => 'required',
        'phase' => 'required',
        'max_current' => 'required',
    ];

    public function meters(): HasMany
    {
        return $this->hasMany(Meter::class);
    }

    public function __toString()
    {
        return sprintf(
            '%s Phase, %s Amper, Online: %s',
            $this->phase,
            $this->max_current,
            $this->online ? 'yes' : 'no'
        );
    }
}
