<?php

namespace App\Models;

use App\Models\Person\Person;

class PersonDocument extends BaseModel
{
    protected $connection = 'test_company_db';
    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
