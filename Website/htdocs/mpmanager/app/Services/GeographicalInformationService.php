<?php

namespace App\Services;

use App\Models\GeographicalInformation;
use Illuminate\Support\Collection;

class GeographicalInformationService implements IBaseService, IAssociative
{
    public function __construct(
        private GeographicalInformation $geographicalInformation
    ) {
    }

    // This function will be removed until devices feature migration is done
    public function changeOwnerWithAddress($meterParameter, $addressId): void
    {
        $geoInfo = $this->geographicalInformation->newQuery()->where('owner_type', 'meter_parameter')->where('owner_id', $meterParameter->id)->first();
        if ($geoInfo) {
            $geoInfo->owner_type = 'address';
            $geoInfo->owner_id = $addressId;
            $geoInfo->save();
        }
    }

    public function getById($id): ?GeographicalInformation
    {
        /** @var GeographicalInformation $result */
        $result = $this->geographicalInformation->newQuery()->find($id);

        return $result;
    }


    public function delete($model): void
    {
        $model->delete();
    }

    public function getAll($limit = null): Collection
    {
        return $this->geographicalInformation->newQuery()->get();
    }

    public function create($data)
    {
        throw new \Exception("not implemented");
    }

    public function update($model, $data)
    {
        throw new \Exception("not implemented");
    }

    public function make($geographicalInformationData): GeographicalInformation
    {
        /** @var GeographicalInformation $result */
        $result = $this->geographicalInformation->newQuery()->make([
            'points' => $geographicalInformationData['points']
        ]);

        return $result;
    }

    public function save($geographicalInformation): GeographicalInformation
    {
        return $geographicalInformation->save();
    }
}
