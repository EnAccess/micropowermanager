<?php

namespace App\Models;

use App\ConnectionGroup;
use App\Models\Meter\MeterParameter;
use App\Models\Meter\MeterTariff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubConnectionType extends BaseModel
{
    public function connectionType(): BelongsTo
    {
        return $this->belongsTo(ConnectionType::class);
    }

    public function meterParameters(): HasMany
    {
        return $this->hasMany(MeterParameter::class, 'connection_type_id');
    }


    public function tariff(): BelongsTo
    {
        return $this->belongsTo(MeterTariff::class, 'tariff_id', 'id');
    }
}
