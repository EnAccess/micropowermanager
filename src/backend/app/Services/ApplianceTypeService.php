<?php

namespace App\Services;

use App\Models\AssetType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ApplianceTypeService {
    private AssetType $assetType;

    public function __construct(AssetType $assetType) {
        $this->assetType = $assetType;
    }

    /**
     * @return LengthAwarePaginator<AssetType>|Collection<int, AssetType>
     */
    public function getApplianceTypes(Request $request): LengthAwarePaginator|Collection {
        $perPage = $request->get('per_page');
        if ($perPage) {
            return $this->assetType->newQuery()->paginate($perPage);
        }

        return $this->assetType->newQuery()->get();
    }

    /**
     * @param array{name?: string, price?: float|int} $data
     */
    public function createApplianceType(array $data): AssetType {
        return $this->assetType::query()
            ->create(
                Arr::only($data, ['name', 'price'])
            );
    }

    /**
     * @param array{name?: string, price?: float|int} $data
     */
    public function updateApplianceType(array $data, AssetType $appliance): AssetType {
        $appliance->update(Arr::only($data, ['name', 'price']));
        $appliance->fresh();

        return $appliance;
    }

    public function deleteApplianceType(AssetType $applianceType): ?bool {
        return $applianceType->delete();
    }
}
