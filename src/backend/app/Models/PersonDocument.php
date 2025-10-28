<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int         $id
 * @property      int         $person_id
 * @property      string      $type
 * @property      string      $name
 * @property      string|null $location
 * @property      Carbon|null $created_at
 * @property      Carbon|null $updated_at
 * @property-read Person|null $person
 */
class PersonDocument extends BaseModel {
    /**
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo {
        return $this->belongsTo(Person::class);
    }
}
