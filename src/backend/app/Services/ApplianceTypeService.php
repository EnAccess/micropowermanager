<?php

namespace App\Services;

use App\Models\AssetType;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ApplianceTypeService
{
    private AssetType $assetType;

    public function __construct(AssetType $assetType)
    {
        $this->assetType = $assetType;
    }

    public function getApplianceTypes(Request $request): LengthAwarePaginator|Collection
    {
        $perPage = $request->get('per_page');
        if ($perPage) {
            return $this->assetType->newQuery()->paginate($perPage);
        }

        return $this->assetType->newQuery()->get();
    }

    public function createApplianceType(Request $request): AssetType
    {
        return $this->assetType::query()
            ->create(
                $request->only(['name', 'price'])
            );
    }

    public function updateApplianceType(Request $request, AssetType $appliance): AssetType
    {
        $appliance->update($request->only(['name', 'price']));
        $appliance->fresh();

        return $appliance;
    }

    public function deleteApplianceType(AssetType $applianceType): void
    {
        $applianceType->delete();
    }
}
