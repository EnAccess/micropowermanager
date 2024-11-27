<?php

namespace App\Models\Meter;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int    $id
 * @property bool   $online
 * @property string $phase
 * @property int    $max_current
 */
class MeterType extends BaseModel {
    use HasFactory;

    public static $rules = [
        'online' => 'required',
        'phase' => 'required',
        'max_current' => 'required',
    ];

    public function meters(): HasMany {
        return $this->hasMany(Meter::class);
    }

    public function __toString() {
        return sprintf(
            '%s Phase, %s Amper, Online: %s',
            $this->phase,
            $this->max_current,
            $this->online ? 'yes' : 'no'
        );
    }
}
