<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use App\Models\Meter\Meter;
use Database\Factories\SubConnectionTypeFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property      int                    $id
 * @property      string                 $name
 * @property      int                    $connection_type_id
 * @property      int                    $tariff_id
 * @property      Carbon|null            $created_at
 * @property      Carbon|null            $updated_at
 * @property-read ConnectionType|null    $connectionType
 * @property-read Collection<int, Meter> $meters
 * @property-read Tariff|null            $tariff
 */
class SubConnectionType extends BaseModel {
    /** @use HasFactory<SubConnectionTypeFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ConnectionType, $this>
     */
    public function connectionType(): BelongsTo {
        return $this->belongsTo(ConnectionType::class);
    }

    /**
     * @return HasMany<Meter, $this>
     */
    public function meters(): HasMany {
        return $this->hasMany(Meter::class, 'connection_type_id');
    }

    /**
     * @return BelongsTo<Tariff, $this>
     */
    public function tariff(): BelongsTo {
        return $this->belongsTo(Tariff::class, 'tariff_id', 'id');
    }
}
