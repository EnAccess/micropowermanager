<?php

namespace App\Services;

use App\Models\Manufacturer;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<Manufacturer>
 */
class ManufacturerService implements IBaseService {
    public function __construct(
        private Manufacturer $manufacturer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function createManufacturerDataFromRequest(Request $request): array {
        return [
            'name' => $request->input('name'),
            'contact_person' => $request->input('contact_person'),
            'website' => $request->input('website'),
            'api_name' => $request->input('api_name'),
        ];
    }

    public function getById(int $manufacturerId): Manufacturer {
        return $this->manufacturer->newQuery()->with(['address.city.country'])->findOrFail($manufacturerId);
    }

    /**
     * @param array<string, mixed> $manufacturerData
     */
    public function create(array $manufacturerData): Manufacturer {
        return $this->manufacturer->newQuery()->create($manufacturerData);
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

    /**
     * @param array<string, mixed> $data
     */
    public function update($model, array $data): Manufacturer {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }
}
