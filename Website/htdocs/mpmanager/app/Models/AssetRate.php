<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Class AssetRate
 *
 * @package App\Models
 *
 * @property $asset_person_id int
 * @property $rate_cost int
 * @property $remaining int
 * @property $due_date string
 */
class AssetRate extends Model
{


    protected $fillable = [
        'asset_person_id',
        'rate_cost',
        'remaining',
        'due_date',
        'remind'
    ];

    public function assetPerson(): BelongsTo
    {
        return $this->belongsTo(AssetPerson::class);
    }

    public function logs(): MorphMany
    {
        return $this->morphMany(Log::class, 'affected');
    }


    public function paymentHistory(): MorphOne
    {
        return $this->morphOne(PaymentHistory::class, 'paid_for');
    }

    public function asset(): HasOneThrough
    {
        return $this->HasOneThrough(AssetType::class, AssetPerson::class, 'asset_type_id', 'id');
    }
}
