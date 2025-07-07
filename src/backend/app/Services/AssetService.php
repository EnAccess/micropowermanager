<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class AssetService {
    public function __construct(private Asset $asset) {}

    /**
     * @return LengthAwarePaginator<Asset>|Collection<int, Asset>
     */
    public function getAssets(Request $request): LengthAwarePaginator|Collection {
        $perPage = $request->get('per_page');
        if ($perPage) {
            return $this->asset->newQuery()->with(['assetType'])->paginate($perPage);
        }

        return $this->asset->newQuery()->with(['assetType'])->get();
    }

    public function createAsset(Request $request): Asset {
        return $this->asset::query()
            ->create(
                $request->only(['asset_type_id', 'name', 'price'])
            );
    }

    public function updateAsset(Request $request, Asset $asset): Asset {
        $asset->update($request->only(['name', 'asset_type_id', 'price']));
        $asset->fresh();

        return $asset;
    }

    public function deleteAsset(Asset $asset): bool {
        return $asset->delete();
    }

    public function getById(int $id): ?Asset {
        return $this->asset->newQuery()->with(['assetType'])->find($id);
    }
}
