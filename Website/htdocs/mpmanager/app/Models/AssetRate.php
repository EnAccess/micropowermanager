<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Class AssetRate.
 *
 * @property int    $asset_person_id
 * @property int    $rate_cost
 * @property int    $remaining
 * @property string $due_date
 * @property AssetPerson assetPerson
 */
class AssetRate extends BaseModel
{
    public const RELATION_NAME = 'asset_rate';

    protected $fillable = [
        'asset_person_id',
        'rate_cost',
        'remaining',
        'due_date',
        'remind',
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
