<?php

namespace App\Models;

use App\Models\Base\BaseModelCore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int    $id
 * @property string $name;
 */
class Company extends BaseModelCore {
    use HasFactory;

    public const COL_ID = 'id';

    // has many Users
    // public function users()
    // {
    //     return $this->hasMany(User::class);
    // }

    public function database(): HasOne {
        return $this->hasOne(CompanyDatabase::class);
    }

    public function databaseProxies(): HasMany {
        return $this->hasMany(DatabaseProxy::class);
    }

    public function getId(): int {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function jobs() {
        return $this->hasMany(CompanyJob::class);
    }
}
