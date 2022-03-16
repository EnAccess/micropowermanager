<?php

namespace App\Services;

use App\Models\AssetType;

class ApplianceTypeService
{
    private $assetType;

    public function __construct(AssetType $assetType)
    {
        $this->assetType = $assetType;
    }

    public function getApplianceTypes($request)
    {
        $perPage = $request->get('per_page');
        if ($perPage) {
            return $this->assetType->newQuery()->paginate($perPage);
        }
        return $this->assetType->newQuery()->get();
    }

    public function createApplianceType($request)
    {
        return $this->assetType::query()
            ->create(
                $request->only(['name', 'price'])
            );
    }

    public function updateApplianceType($request, $appliance)
    {
        $appliance->update($request->only(['name', 'price']));
        $appliance->fresh();
        return $appliance;
    }

    public function deleteApplianceType($applianceType)
    {
        $applianceType->delete();
    }
}
