<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CompanyDatabase extends BaseModel
{
    use HasFactory;
    protected $connection = 'micro_power_manager';

    // has one company
    public function company(): HasOne
    {
        return $this->HasOne(Company::class);
    }
}
