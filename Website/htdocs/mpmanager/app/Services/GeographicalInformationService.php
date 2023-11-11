<?php

namespace App\Services;

use App\Models\GeographicalInformation;
use App\Models\Meter\MeterParameter;

class GeographicalInformationService implements IBaseService
{
    public function __construct(
        private GeographicalInformation $geographicalInformation
    ) {
    }

    // This function will be removed until devices feature migration is done
    public function changeOwnerWithDevice($meterParameterId)
    {
        $geoInfo = $this->geographicalInformation->newQuery()->where('owner_type', 'meter_parameter')->where('owner_id', $meterParameterId)->first();

        if ($geoInfo) {
            $geoInfo->owner_type = 'device';
            $geoInfo->save();
        }
    }

    public function getById($id)
    {
        return $this->geographicalInformation->newQuery()->find($id);
    }


    public function delete($model)
    {
        $model->delete();
    }

    public function getAll($limit = null)
    {
        return $this->geographicalInformation->newQuery()->get();
    }

    public function create($data)
    {
        // TODO: Implement create() method.
    }

    public function update($model, $data)
    {
        // TODO: Implement update() method.
    }

}
