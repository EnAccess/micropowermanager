<?php

namespace App\Services;

use App\Models\EBike;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<EBike>
 */
class EBikeService implements IBaseService {
    /** @use HasCrudOperations<EBike> */
    use HasCrudOperations;

    public function __construct(
        private EBike $eBike,
    ) {}

    protected function crudModel(): EBike {
        return $this->eBike;
    }

    /**
     * @return Collection<int, EBike>|LengthAwarePaginator<int, EBike>
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
     * @return LengthAwarePaginator<int, EBike>
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
}
