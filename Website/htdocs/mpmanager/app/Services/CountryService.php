<?php


namespace App\Services;

use App\Models\Country;

class CountryService
{

    public function __construct(private SessionService $sessionService,private Country $country)
    {
        $this->sessionService->setModel($country);
    }

    public function getByCode(string|null $countryCode)
    {
        return $countryCode !==null? $this->country->where('country_code', $countryCode)->first():$countryCode;
    }
}
