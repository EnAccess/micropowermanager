<?php

namespace App\Services;

use App\Models\GeographicalInformation;
use App\Services\Interfaces\IAssociative;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<GeographicalInformation>
 * @implements IAssociative<GeographicalInformation>
 */
class GeographicalInformationService implements IBaseService, IAssociative {
    public function __construct(
        private GeographicalInformation $geographicalInformation,
    ) {}

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

    public function getById(int $id): GeographicalInformation {
        return $this->geographicalInformation->newQuery()->find($id);
    }

    public function delete($model): ?bool {
        return $model->delete();
    }

    /**
     * @return Collection<int, GeographicalInformation>|LengthAwarePaginator<GeographicalInformation>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        return $this->geographicalInformation->newQuery()->get();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): GeographicalInformation {
        throw new \Exception('Method create() not yet implemented.');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update($model, array $data): GeographicalInformation {
        $model->update($data);
        $model->fresh();

        return $model;
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
