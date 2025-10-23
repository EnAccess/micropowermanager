<?php

namespace Inensus\ViberMessaging\Models;

use App\Models\Base\BaseModel;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int         $id
 * @property      int         $person_id
 * @property      string      $viber_id
 * @property      string      $registered_meter_serial_number
 * @property      Carbon|null $created_at
 * @property      Carbon|null $updated_at
 * @property-read Person|null $mpmPerson
 */
class ViberContact extends BaseModel {
    protected $table = 'viber_contacts';

    /**
     * @return BelongsTo<Person, $this>
     */
    public function mpmPerson(): BelongsTo {
        return $this->belongsTo(Person::class, 'person_id');
    }
}
