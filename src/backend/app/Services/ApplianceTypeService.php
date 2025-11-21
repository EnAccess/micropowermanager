<?php

namespace App\Services;

use App\Models\ApplianceType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ApplianceTypeService {
    public function __construct(private ApplianceType $applianceType) {}

    /**
     * @return LengthAwarePaginator<int, ApplianceType>|Collection<int, ApplianceType>
     */
    public function getApplianceTypes(Request $request): LengthAwarePaginator|Collection {
        $perPage = $request->get('per_page');
        if ($perPage) {
            return $this->applianceType->newQuery()->paginate($perPage);
        }

        return $this->applianceType->newQuery()->get();
    }

    /**
     * @param array{name?: string, price?: float|int} $data
     */
    public function createApplianceType(array $data): ApplianceType {
        return $this->applianceType::query()
            ->create(
                Arr::only($data, ['name', 'price'])
            );
    }

    /**
     * @param array{name?: string, price?: float|int} $data
     */
    public function updateApplianceType(array $data, ApplianceType $appliance): ApplianceType {
        $appliance->update(Arr::only($data, ['name', 'price']));
        $appliance->fresh();

        return $appliance;
    }

    public function deleteApplianceType(ApplianceType $applianceType): ?bool {
        return $applianceType->delete();
    }
}
