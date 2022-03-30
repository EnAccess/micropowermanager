<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends BaseModel
{

    protected $connection = 'test_company_db';
    /**
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'country_code';
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
