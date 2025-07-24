<?php

namespace App\Models\Meter;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Database\Factories\Meter\MeterTypeFactory;

/**
 * @property int    $id
 * @property bool   $online
 * @property string $phase
 * @property int    $max_current
 *
 * @use HasFactory<MeterTypeFactory>
 */
class MeterType extends BaseModel {
    /** @use HasFactory<MeterTypeFactory> */
    use HasFactory;

    /** @var array<string, string> */
    public static array $rules = [
        'online' => 'required',
        'phase' => 'required',
        'max_current' => 'required',
    ];

    /**
     * @return HasMany<Meter, MeterType>
     */
    public function meters(): HasMany {
        return $this->hasMany(Meter::class);
    }

    public function __toString(): string {
        return sprintf(
            '%s Phase, %s Amper, Online: %s',
            $this->phase,
            $this->max_current,
            $this->online ? 'yes' : 'no'
        );
    }
}
