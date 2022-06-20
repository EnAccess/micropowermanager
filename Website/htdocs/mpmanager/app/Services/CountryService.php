<?php


namespace App\Services;

use App\Models\Country;

class CountryService  implements IBaseService
{

    public function __construct(private Country $country)
    {


    }

    public function getByCode(string|null $countryCode)
    {
        return $countryCode !==null? $this->country->where('country_code', $countryCode)->first():$countryCode;
    }

    public function getById($id)
    {
        // TODO: Implement getById() method.
    }

    public function create($data)
    {
        // TODO: Implement create() method.
    }

    public function update($model, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }

    public function getAll($limit = null)
    {
        // TODO: Implement getAll() method.
    }
}
