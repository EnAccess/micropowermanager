<?php

namespace Inensus\KelinMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property      int                         $id
 * @property      int                         $mpm_customer_id
 * @property      string                      $customer_no
 * @property      string                      $address
 * @property      string                      $mobile
 * @property      string|null                 $hash
 * @property      Carbon|null                 $created_at
 * @property      Carbon|null                 $updated_at
 * @property-read Collection<int, KelinMeter> $kelinMeters
 * @property-read Person|null                 $mpmPerson
 */
class KelinCustomer extends BaseModel {
    protected $table = 'kelin_customers';

    /**
     * @return BelongsTo<Person, $this>
     */
    public function mpmPerson(): BelongsTo {
        return $this->belongsTo(Person::class, 'mpm_customer_id');
    }

    /**
     * @return HasMany<KelinMeter, $this>
     */
    public function kelinMeters(): HasMany {
        return $this->hasMany(KelinMeter::class, 'customer_no', 'customer_no');
    }
}
