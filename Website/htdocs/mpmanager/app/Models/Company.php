<?php

namespace App\Models;

use App\Models\Base\MasterModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int    $id
 * @property string $name;
 */
class Company extends MasterModel
{
    use HasFactory;

    public const COL_ID = 'id';

    // has many Users
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // has one company database
    public function database()
    {
        return $this->hasOne(CompanyDatabase::class);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    // has many company jobs
    public function jobs()
    {
        return $this->hasMany(CompanyJob::class);
    }
}
