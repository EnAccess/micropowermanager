<?php

namespace App\Services;

use App\Models\GeographicalInformation;
use App\Services\Interfaces\IAssociative;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;

/**
 * @implements IBaseService<GeographicalInformation>
 * @implements IAssociative<GeographicalInformation>
 */
class GeographicalInformationService implements IBaseService, IAssociative {
    /** @use HasCrudOperations<GeographicalInformation> */
    use HasCrudOperations;

    public function __construct(
        private GeographicalInformation $geographicalInformation,
    ) {}

    protected function crudModel(): GeographicalInformation {
        return $this->geographicalInformation;
    }

    // This function will be removed until devices feature migration is done
    public function changeOwnerWithAddress(object $meter, int $addressId): void {
        $geoInfo = $this->geographicalInformation->newQuery()
            ->where('owner_type', 'meter')
            ->where('owner_id', $meter->id)
            ->first();

        if ($geoInfo) {
            $geoInfo->owner_type = 'address';
            $geoInfo->owner_id = $addressId;
            $geoInfo->save();
        }
    }

    /**
     * @param array<string, mixed> $geographicalInformationData
     */
    public function make(array $geographicalInformationData): GeographicalInformation {
        return $this->geographicalInformation->newQuery()->make([
            'points' => $geographicalInformationData['points'],
        ]);
    }

    public function save($geographicalInformation): bool {
        return $geographicalInformation->save();
    }
}
