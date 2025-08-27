<?php

namespace Inensus\ViberMessaging\Models;

use App\Models\Base\BaseModel;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViberContact extends BaseModel {
    protected $table = 'viber_contacts';

    public function mpmPerson(): BelongsTo {
        return $this->belongsTo(Person::class, 'person_id');
    }
}
