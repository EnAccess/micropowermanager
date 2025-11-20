<?php

namespace App\Models;

use App\Events\AppliancePersonCreated;
use App\Models\Base\BaseModel;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Class AppliancePerson.
 *
 * @property      int                        $id
 * @property      int                        $appliance_id
 * @property      int                        $person_id
 * @property      float                      $total_cost
 * @property      int                        $rate_count
 * @property      string                     $creator_type
 * @property      int                        $creator_id
 * @property      Carbon|null                $created_at
 * @property      Carbon|null                $updated_at
 * @property      float|null                 $down_payment
 * @property      Carbon|null                $first_payment_date
 * @property      string|null                $device_serial
 * @property-read Appliance|null                 $appliance
 * @property-read Model                      $creator
 * @property-read Device|null                $device
 * @property-read Collection<int, Log>       $logs
 * @property-read Person|null                $person
 * @property-read Collection<int, ApplianceRate> $rates
 */
class AppliancePerson extends BaseModel {
    /** @var array<string, string> */
    protected $dispatchesEvents = [
        'created' => AppliancePersonCreated::class,
    ];

    /**
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo {
        return $this->belongsTo(Person::class);
    }

    /**
     * @return MorphMany<Log, $this>
     */
    public function logs(): MorphMany {
        return $this->morphMany(Log::class, 'affected');
    }

    /**
     * @return BelongsTo<Appliance, $this>
     */
    public function appliance(): BelongsTo {
        return $this->belongsTo(Appliance::class, 'appliance_id');
    }

    /**
     * @return HasMany<ApplianceRate, $this>
     */
    public function rates(): HasMany {
        return $this->hasMany(ApplianceRate::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function creator(): MorphTo {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Device, $this>
     */
    public function device(): BelongsTo {
        return $this->belongsTo(Device::class, 'device_serial', 'device_serial');
    }
}
