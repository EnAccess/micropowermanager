<?php

namespace MPM\EBike;

use App\Models\EBike;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<EBike>
 */
class EBikeService implements IBaseService {
    public function __construct(
        private EBike $eBike,
    ) {}

    /**
     * @return Collection<int, EBike>|LengthAwarePaginator<EBike>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit) {
            return $this->eBike->newQuery()->with(['manufacturer', 'appliance', 'device.person'])->paginate($limit);
        }

        return $this->eBike->newQuery()->with(['manufacturer', 'appliance', 'device.person'])->get();
    }

    public function getById(int $id): EBike {
        return $this->eBike->newQuery()->with(['manufacturer', 'appliance', 'device.person'])->find($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): EBike {
        return $this->eBike->newQuery()->create($data);
    }

    /**
     * @return LengthAwarePaginator<EBike>
     */
    public function search(string $term, int $paginate): LengthAwarePaginator {
        return $this->eBike->newQuery()
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
    public function update($model, array $data): EBike {
        $model->newQuery()->update($data);
        $model->fresh();

        return $model;
    }

    public function getBySerialNumber(string $serialNumber): ?EBike {
        return $this->eBike->newQuery()
            ->with(['manufacturer', 'appliance', 'device.person'])->where(
                'serial_number',
                $serialNumber
            )->first();
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }
}
