<?php

namespace MPM\SolarHomeSystem;

use App\Models\SolarHomeSystem;
use App\Services\IBaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SolarHomeSystemService implements IBaseService
{
    public function __construct(private SolarHomeSystem $solarHomeSystem)
    {
    }

    public function getAll($limit = null): LengthAwarePaginator|Collection
    {
        if ($limit) {
            return $this->solarHomeSystem->newQuery()->with(['manufacturer', 'appliance', 'device.person'])->paginate($limit);
        }
        return $this->solarHomeSystem->newQuery()->with(['manufacturer', 'appliance', 'device.person'])->get();
    }

    public function getById($id): SolarHomeSystem
    {
        /** @var SolarHomeSystem|null $result */
        $result = $this->solarHomeSystem->newQuery()
            ->with(['manufacturer', 'appliance', 'device.person'])
            ->find($id);

        return $result;
    }

    public function create($data): SolarHomeSystem
    {
        /** @var SolarHomeSystem $result */
        $result = $this->solarHomeSystem->newQuery()->create($data);

        return $result;
    }

    public function search($term, $paginate): LengthAwarePaginator
    {
        return $this->solarHomeSystem->newQuery()
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
        throw new \Exception("not implemented");
    }

    public function delete($model)
    {
        throw new \Exception("not implemented");
    }
}
