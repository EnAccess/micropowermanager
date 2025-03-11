<?php

namespace App\Models\AccessRate;

use App\Models\Base\BaseModel;
use App\Models\Meter\MeterTariff;
use App\Models\PaymentHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Class AccessRate.
 *
 * @property int $id
 * @property int $amount
 * @property int $tariff_id
 * @property int $period    when the payment repeats itself
 */
class AccessRate extends BaseModel {
    use HasFactory;

    public const RELATION_NAME = 'access_rate';

    public function tariff(): BelongsTo {
        return $this->belongsTo(MeterTariff::class, 'tariff_id', 'id');
    }

    public function accessRatePayments(): HasMany {
        return $this->hasMany(AccessRatePayment::class);
    }

    public function __toString() {
        return sprintf('For tariff : %s', $this->tariff()->first()->name);
    }

    public function paymentHistories(): MorphMany {
        return $this->morphMany(PaymentHistory::class, 'paid_for');
    }
}
