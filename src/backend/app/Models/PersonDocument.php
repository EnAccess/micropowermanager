<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use App\Models\Person\Person;

class PersonDocument extends BaseModel {
    public function person() {
        return $this->belongsTo(Person::class);
    }
}
