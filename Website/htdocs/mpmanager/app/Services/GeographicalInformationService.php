<?php

namespace App\Services;

use App\Models\GeographicalInformation;

class GeographicalInformationService  implements IBaseService
{
    public function __construct(
        private GeographicalInformation $geographicalInformation
    ) {

    }


    public function makeGeographicalInformation($geoPoints)
    {
        return $this->geographicalInformation->newQuery()->make([
            'points' => $geoPoints,
        ]);
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
