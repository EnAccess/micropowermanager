<?php

namespace App\Services;

use App\Models\Appliance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class ApplianceService {
    public function __construct(private Appliance $appliance) {}

    /**
     * @return LengthAwarePaginator<int, Appliance>|Collection<int, Appliance>
     */
    public function getAppliances(Request $request): LengthAwarePaginator|Collection {
        $perPage = $request->get('per_page');
        if ($perPage) {
            return $this->appliance->newQuery()->with(['applianceType'])->paginate($perPage);
        }

        return $this->appliance->newQuery()->with(['applianceType'])->get();
    }

    public function createAppliance(Request $request): Appliance {
        return $this->appliance::query()
            ->create(
                $request->only(['appliance_type_id', 'name', 'price'])
            );
    }

    public function updateAppliance(Request $request, Appliance $appliance): Appliance {
        $appliance->update($request->only(['name', 'appliance_type_id', 'price']));
        $appliance->fresh();

        return $appliance;
    }

    public function deleteAppliance(Appliance $appliance): Appliance {
        $applianceCopy = clone $appliance;
        $appliance->delete();

        return $applianceCopy;
    }

    public function getById(int $id): ?Appliance {
        return $this->appliance->newQuery()->with(['applianceType'])->find($id);
    }

    /**
     * @return Collection<int, Appliance>
     */
    public function getAllForExport(): Collection {
        return $this->appliance->newQuery()->with([
            'applianceType',
            'agentAssignedAppliance',
            'rates',
        ])->get();
    }
}
