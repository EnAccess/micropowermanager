<?php

namespace App\Models\Meter;

use App\Models\Base\BaseModel;
use Database\Factories\Meter\MeterTypeFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @use HasFactory<MeterTypeFactory>
 *
 * @property      int                    $id
 * @property      bool                   $online
 * @property      int                    $phase
 * @property      int                    $max_current
 * @property      Carbon|null            $created_at
 * @property      Carbon|null            $updated_at
 * @property-read Collection<int, Meter> $meters
 */
class MeterType extends BaseModel {
    /** @use HasFactory<MeterTypeFactory> */
    use HasFactory;

    /** @var array<string, string> */
    public static $rules = [
        'online' => 'required',
        'phase' => 'required',
        'max_current' => 'required',
    ];

    /**
     * @return HasMany<Meter, $this>
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
