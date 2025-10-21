<?php

namespace Inensus\KelinMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Person\Person;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int                         $id
 * @property int                         $mpm_customer_id
 * @property string                      $customer_no
 * @property string                      $address
 * @property string                      $mobile
 * @property string                      $hash
 * @property int                         $id
 * @property int                         $id
 * @property Carbon                      $created_at
 * @property Carbon                      $updated_at
 * @property Person                      $mpmPerson
 * @property Collection<int, KelinMeter> $kelinMeters
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
