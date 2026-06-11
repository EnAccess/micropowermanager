<?php

namespace App\Services;

use App\Models\Manufacturer;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<Manufacturer>
 */
class ManufacturerService implements IBaseService {
    /** @use HasCrudOperations<Manufacturer> */
    use HasCrudOperations;

    public function __construct(
        private Manufacturer $manufacturer,
    ) {}

    protected function crudModel(): Manufacturer {
        return $this->manufacturer;
    }

    /**
     * @return array<string, mixed>
     */
    public function createManufacturerDataFromRequest(Request $request): array {
        return [
            'name' => $request->get('name'),
            'contact_person' => $request->get('contact_person'),
            'website' => $request->get('website'),
            'api_name' => $request->get('api_name'),
        ];
    }

    public function getById(int $manufacturerId): Manufacturer {
        return $this->manufacturer->newQuery()->with(['address.city.country'])->findOrFail($manufacturerId);
    }

    /**
     * @return Collection<int, Manufacturer>|LengthAwarePaginator<int, Manufacturer>
     */
    public function getAll(?int $limit = null, ?string $type = null): Collection|LengthAwarePaginator {
        $query = $this->manufacturer->newQuery()
            ->when($type !== null, fn ($q) => $q->where('type', $type));

        if ($limit) {
            return $query->paginate($limit);
        }

        return $query->get();
    }

    public function getByName(string $manufacturerName): ?Manufacturer {
        return $this->manufacturer->newQuery()->where('name', $manufacturerName)->first();
    }
}
