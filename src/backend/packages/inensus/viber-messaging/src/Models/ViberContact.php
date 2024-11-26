<?php

namespace Inensus\ViberMessaging\Models;

use App\Models\Base\BaseModel;
use App\Models\Person\Person;

class ViberContact extends BaseModel {
    protected $table = 'viber_contacts';

    public function mpmPerson() {
        return $this->belongsTo(Person::class, 'person_id');
    }
}
