<?php

namespace App\Models;

class CompanyJob extends MasterModel
{
    const STATUS_PENDING = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAILED = -1;

    // has one company
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}