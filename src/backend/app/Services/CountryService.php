<?php

namespace App\Services;

use App\Models\Country;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;

/**
 * @implements IBaseService<Country>
 */
class CountryService implements IBaseService {
    /** @use HasCrudOperations<Country> */
    use HasCrudOperations;

    public function __construct(
        private Country $country,
    ) {}

    protected function crudModel(): Country {
        return $this->country;
    }

    public function getByCode(?string $countryCode): ?Country {
        return $countryCode !== null ? $this->country->where('country_code', $countryCode)->first() : $countryCode;
    }
}
