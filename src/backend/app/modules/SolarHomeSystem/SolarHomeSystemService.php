<?php

namespace MPM\SolarHomeSystem;

use App\Models\SolarHomeSystem;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<SolarHomeSystem>
 */
class SolarHomeSystemService implements IBaseService {
    public function __construct(private SolarHomeSystem $solarHomeSystem) {}

    /**
     * @return Collection<int, SolarHomeSystem>|LengthAwarePaginator<SolarHomeSystem>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit) {
            return $this->solarHomeSystem->newQuery()->with(['manufacturer', 'appliance', 'device.person'])->paginate($limit);
        }

        return $this->solarHomeSystem->newQuery()->with(['manufacturer', 'appliance', 'device.person'])->get();
    }

    public function getById(int $id): SolarHomeSystem {
        /** @var SolarHomeSystem|null $result */
        $result = $this->solarHomeSystem->newQuery()
            ->with(['manufacturer', 'appliance', 'device.person', 'device.address.geo', 'tokens'])
            ->find($id);

        return $result;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): SolarHomeSystem {
        /** @var SolarHomeSystem $result */
        $result = $this->solarHomeSystem->newQuery()->create($data);

        return $result;
    }

    /**
     * @return LengthAwarePaginator<SolarHomeSystem>
     */
    public function search(int $term, int $paginate): LengthAwarePaginator {
        return $this->solarHomeSystem->newQuery()
            ->with(['manufacturer', 'appliance', 'device.person'])
            ->whereHas(
                'device',
                fn ($q) => $q->whereHas(
                    'person',
                    fn ($q) => $q->where('name', 'LIKE', '%'.$term.'%')
                        ->orWhere('surname', 'LIKE', '%'.$term.'%')
                )
            )
            ->orWhere(
                'serial_number',
                'LIKE',
                '%'.$term.'%'
            )->paginate($paginate);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update($model, array $data): SolarHomeSystem {
        throw new \Exception('not implemented');
    }

    public function delete($model): ?bool {
        throw new \Exception('not implemented');
    }
}
