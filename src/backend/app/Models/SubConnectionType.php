<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterTariff;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubConnectionType extends BaseModel {
    /** @use HasFactory<\Database\Factories\SubConnectionTypeFactory> */
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
     * @return BelongsTo<MeterTariff, $this>
     */
    public function tariff(): BelongsTo {
        return $this->belongsTo(MeterTariff::class, 'tariff_id', 'id');
    }
}
