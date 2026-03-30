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
 * Class ApplianceRate.
 *
 * @property      int                  $id
 * @property      int                  $appliance_person_id
 * @property      int                  $rate_cost
 * @property      int                  $remaining
 * @property      Carbon               $due_date
 * @property      int                  $remind
 * @property      Carbon|null          $created_at
 * @property      Carbon|null          $updated_at
 * @property-read ApplianceType|null   $appliance
 * @property-read AppliancePerson|null $appliancePerson
 * @property-read Collection<int, Log> $logs
 * @property-read PaymentHistory|null  $paymentHistory
 */
class ApplianceRate extends BaseModel {
    public const RELATION_NAME = 'appliance_rate';

    protected $fillable = [
        'appliance_person_id',
        'rate_cost',
        'remaining',
        'due_date',
        'remind',
    ];

    /**
     * @return BelongsTo<AppliancePerson, $this>
     */
    public function appliancePerson(): BelongsTo {
        return $this->belongsTo(AppliancePerson::class);
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
     * @return HasOneThrough<ApplianceType, AppliancePerson, $this>
     */
    public function appliance(): HasOneThrough {
        return $this->hasOneThrough(ApplianceType::class, AppliancePerson::class, 'id', 'appliance_type_id', 'appliance_person_id', 'id');
    }
}
