<?php

namespace App\Models;

class CompanyJob extends MasterModel
{
    public const STATUS_PENDING = 0;
    public const STATUS_SUCCESS = 1;
    public const STATUS_FAILED = -1;

    // has one company
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
