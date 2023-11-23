<?php

namespace MPM\SolarHomeSystem;

use App\Models\SolarHomeSystem;
use App\Services\IBaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SolarHomeSystemService implements IBaseService
{

    public function __construct(private SolarHomeSystem $solarHomeSystem)
    {
    }

    public function getAll($limit = null)
    {
        if ($limit) {
            return $this->solarHomeSystem->newQuery()->with(['manufacturer', 'appliance', 'device.person'])->paginate
            ($limit);
        }
        return $this->solarHomeSystem->newQuery()->with(['manufacturer', 'appliance', 'device.person'])->get();
    }

    public function getById($id)
    {
        return $this->solarHomeSystem->newQuery()->with(['manufacturer', 'appliance', 'device.person'])->find($id);
    }

    public function create($data)
    {
        return $this->solarHomeSystem->newQuery()->create($data);
    }

    public function search($term, $paginate): LengthAwarePaginator
    {
        return $this->solarHomeSystem->newQuery()
            ->with(['manufacturer', 'appliance', 'device.person'])
            ->whereHas('device',
                fn($q) => $q->whereHas('person',
                    fn($q) => $q->where('name', 'LIKE', '%' . $term . '%')
                        ->orWhere('surname', 'LIKE', '%' . $term . '%')))
            ->orWhere(
                'serial_number',
                'LIKE',
                '%' . $term . '%'
            )->paginate($paginate);
    }

    public function update($model, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }

}
