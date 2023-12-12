<?php

namespace App\Services;

use App\Models\Asset;

class AssetService
{
    public function __construct(private Asset $asset)
    {
    }

    public function getAssets($request)
    {
        $perPage = $request->get('per_page');
        if ($perPage) {
            return $this->asset->newQuery()->with(['assetType'])->paginate($perPage);
        }
        return $this->asset->newQuery()->with(['assetType'])->get();
    }

    public function createAsset($request)
    {
        return $this->asset::query()
            ->create(
                $request->only(['asset_type_id', 'name', 'price'])
            );
    }

    public function updateAsset($request, $asset)
    {
        $asset->update($request->only(['name', 'asset_type_id', 'price']));
        $asset->fresh();
        return $asset;
    }

    public function deleteAsset($asset)
    {
        $asset->delete();
    }

    public function getById($id)
    {
        return $this->asset->newQuery()->with(['assetType'])->find($id);
    }
}
