<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * Class AssetRate.
 *
 * @property      int                  $id
 * @property      int                  $asset_person_id
 * @property      int                  $rate_cost
 * @property      int                  $remaining
 * @property      Carbon               $due_date
 * @property      int                  $remind
 * @property      Carbon|null          $created_at
 * @property      Carbon|null          $updated_at
 * @property-read AssetType|null       $asset
 * @property-read AssetPerson|null     $assetPerson
 * @property-read Collection<int, Log> $logs
 * @property-read PaymentHistory|null  $paymentHistory
 */
class AssetRate extends BaseModel {
    public const RELATION_NAME = 'asset_rate';

    protected $fillable = [
        'asset_person_id',
        'rate_cost',
        'remaining',
        'due_date',
        'remind',
    ];

    /**
     * @return BelongsTo<AssetPerson, $this>
     */
    public function assetPerson(): BelongsTo {
        return $this->belongsTo(AssetPerson::class);
    }

    /**
     * @return MorphMany<Log, $this>
     */
    public function logs(): MorphMany {
        return $this->morphMany(Log::class, 'affected');
    }

    /**
     * @return MorphOne<PaymentHistory, $this>
     */
    public function paymentHistory(): MorphOne {
        return $this->morphOne(PaymentHistory::class, 'paid_for');
    }

    /**
     * @return HasOneThrough<AssetType, AssetPerson, $this>
     */
    public function asset(): HasOneThrough {
        return $this->hasOneThrough(AssetType::class, AssetPerson::class, 'id', 'asset_type_id', 'asset_person_id', 'id');
    }
}
