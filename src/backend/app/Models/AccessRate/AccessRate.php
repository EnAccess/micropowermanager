<?php

namespace App\Models\AccessRate;

use App\Models\Base\BaseModel;
use App\Models\PaymentHistory;
use App\Models\Tariff;
use Database\Factories\AccessRate\AccessRateFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * Class AccessRate.
 *
 * @property      int                                $id
 * @property      int                                $tariff_id
 * @property      float                              $amount
 * @property      int                                $period
 * @property      Carbon|null                        $created_at
 * @property      Carbon|null                        $updated_at
 * @property-read Collection<int, AccessRatePayment> $accessRatePayments
 * @property-read Collection<int, PaymentHistory>    $paymentHistories
 * @property-read Tariff|null                        $tariff
 */
class AccessRate extends BaseModel {
    /** @use HasFactory<AccessRateFactory> */
    use HasFactory;

    public const RELATION_NAME = 'access_rate';

    /**
     * @return BelongsTo<Tariff, $this>
     */
    public function tariff(): BelongsTo {
        return $this->belongsTo(Tariff::class, 'tariff_id', 'id');
    }

    /**
     * @return HasMany<AccessRatePayment, $this>
     */
    public function accessRatePayments(): HasMany {
        return $this->hasMany(AccessRatePayment::class);
    }

    public function __toString(): string {
        return sprintf('For tariff : %s', $this->tariff()->first()->name);
    }

    /**
     * @return MorphMany<PaymentHistory, $this>
     */
    public function paymentHistories(): MorphMany {
        return $this->morphMany(PaymentHistory::class, 'paid_for');
    }
}
