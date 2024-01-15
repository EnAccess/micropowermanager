<?php

namespace MPM\EBike;

use App\Models\EBike;
use App\Models\SolarHomeSystem;
use App\Services\IBaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EBikeService implements IBaseService
{
    public function __construct(private EBike $eBike)
    {
    }

    public function getAll($limit = null)
    {
        if ($limit) {
            return $this->eBike->newQuery()->with(['manufacturer', 'appliance', 'device.person'])->paginate($limit);
        }
        return $this->eBike->newQuery()->with(['manufacturer', 'appliance', 'device.person'])->get();
    }

    public function getById($id)
    {
        return $this->eBike->newQuery()->with(['manufacturer', 'appliance', 'device.person'])->find($id);
    }

    public function create($data)
    {
        return $this->eBike->newQuery()->create($data);
    }

    public function search($term, $paginate): LengthAwarePaginator
    {
        return $this->eBike->newQuery()
            ->with(['manufacturer', 'appliance', 'device.person'])
            ->whereHas(
                'device',
                fn($q) => $q->whereHas(
                    'person',
                    fn($q) => $q->where('name', 'LIKE', '%' . $term . '%')
                    ->orWhere('surname', 'LIKE', '%' . $term . '%')
                )
            )
            ->orWhere(
                'serial_number',
                'LIKE',
                '%' . $term . '%'
            )->paginate($paginate);
    }

    public function update($model, $data)
    {
        return $model->newQuery()->update($data);
    }

    public function getBySerialNumber($serialNumber)
    {
        return $this->eBike->newQuery()
            ->with(['manufacturer', 'appliance', 'device.person'])->where(
                'serial_number',
                $serialNumber
            )->first();
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }
}
