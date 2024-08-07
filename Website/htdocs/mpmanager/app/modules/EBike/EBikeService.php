<?php

namespace MPM\EBike;

use App\Models\EBike;
use App\Services\Interfaces\IBaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EBikeService implements IBaseService
{
    public function __construct(
        private EBike $eBike
    ) {
    }

    public function getAll($limit = null)
    {
        if ($limit) {
            return $this->eBike->newQuery()->with(['manufacturer', 'appliance', 'device.person'])->paginate($limit);
        }

        return $this->eBike->newQuery()->with(['manufacturer', 'appliance', 'device.person'])->get();
    }

    public function getById(int $id): EBike
    {
        return $this->eBike->newQuery()->with(['manufacturer', 'appliance', 'device.person'])->find($id);
    }

    public function create(array $data): EBike
    {
        return $this->eBike->newQuery()->create($data);
    }

    public function search($term, $paginate): LengthAwarePaginator
    {
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

    public function update($model, array $data): EBike
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

    public function delete($model): ?bool
    {
        throw new \Exception('Method delete() not yet implemented.');
    }
}
