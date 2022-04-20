<?php


namespace App\Services;

use App\Models\Country;

class CountryService extends BaseService
{

    public function __construct(private Country $country)
    {
        parent::__construct([$country]);

    }

    public function getByCode(string|null $countryCode)
    {
        return $countryCode !==null? $this->country->where('country_code', $countryCode)->first():$countryCode;
    }
}
