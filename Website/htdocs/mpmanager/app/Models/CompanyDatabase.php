<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CompanyDatabase extends BaseModel
{
    use HasFactory;


    // has one company
    public function company(): HasOne
    {
        return $this->HasOne(Company::class);
    }
}
