<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class Company extends HybridModel
{
    use HasFactory;

    public const COL_ID = 'id';

    //has many Users
    public function users()
    {
        return $this->hasMany(User::class);
    }
    // has one company database
    public function database()
    {
        return $this->hasOne(CompanyDatabase::class);
    }
}
