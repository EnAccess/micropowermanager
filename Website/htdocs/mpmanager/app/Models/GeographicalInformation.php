<?php

namespace App\Models;

use App\Models\Address\Address;
use App\Models\Meter\MeterParameter;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class GeographicalInformation
 *
 * @package App\Models
 *
 * @property string $points
 */
class GeographicalInformation extends BaseModel
{
    protected $connection = 'test_company_db';
    protected $table = 'geographical_informations';

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }


    public function meters(): HasMany
    {
        return $this->hasMany(MeterParameter::class);
    }

    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
