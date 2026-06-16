<?php

namespace App\Services;

use App\Models\SolarHomeSystem;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @implements IBaseService<SolarHomeSystem>
 */
class SolarHomeSystemService implements IBaseService {
    /** @use HasCrudOperations<SolarHomeSystem> */
    use HasCrudOperations;

    public function __construct(private SolarHomeSystem $solarHomeSystem) {}

    protected function crudModel(): SolarHomeSystem {
        return $this->solarHomeSystem;
    }

    /**
     * @return Collection<int, SolarHomeSystem>|LengthAwarePaginator<int, SolarHomeSystem>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        $query = $this->solarHomeSystem->newQuery()->with(['manufacturer', 'appliance', 'device.person']);

        if ($limit) {
            return QueryBuilder::for($query)
                ->allowedSorts($this->allowedSorts())
                ->defaultSort('-created_at')
                ->paginate($limit);
        }

        return QueryBuilder::for($query)
            ->allowedSorts($this->allowedSorts())
            ->defaultSort('-created_at')
            ->get();
    }

    public function getById(int $id): SolarHomeSystem {
        return $this->solarHomeSystem->newQuery()
            ->with(['manufacturer', 'appliance', 'device.person.addresses', 'device.geo', 'tokens'])
            ->findOrFail($id);
    }

    /**
     * @return LengthAwarePaginator<int, SolarHomeSystem>
     */
    public function search(string $term, int $paginate): LengthAwarePaginator {
        $query = $this->solarHomeSystem->newQuery()
            ->with(['manufacturer', 'appliance', 'device.person'])
            ->where(function ($q) use ($term) {
                $q->whereHas(
                    'device',
                    fn ($q) => $q->whereHas(
                        'person',
                        fn ($q) => $q->where('name', 'LIKE', '%'.$term.'%')
                            ->orWhere('surname', 'LIKE', '%'.$term.'%')
                    )
                )->orWhere('serial_number', 'LIKE', '%'.$term.'%');
            });

        return QueryBuilder::for($query)
            ->allowedSorts($this->allowedSorts())
            ->defaultSort('-created_at')
            ->paginate($paginate);
    }

    /**
     * @return array<int, string|AllowedSort>
     */
    private function allowedSorts(): array {
        return [
            'id',
            'serial_number',
            'created_at',
            'updated_at',
            AllowedSort::callback('owner', function (Builder $query, bool $descending) {
                $direction = $descending ? 'desc' : 'asc';

                $subquery = DB::table('devices')
                    ->selectRaw('CONCAT(p.name, " ", p.surname)')
                    ->join('people as p', 'p.id', '=', 'devices.person_id')
                    ->whereColumn('devices.device_id', 'solar_home_systems.id')
                    ->where('devices.device_type', 'solar_home_system')
                    ->limit(1);

                $query->orderByRaw('('.$subquery->toSql().') '.$direction)
                    ->addBinding($subquery->getBindings(), 'order');
            }),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update($model, array $data): SolarHomeSystem {
        $model->update($data);

        return $model->fresh(['manufacturer', 'appliance', 'device.person']);
    }
}
