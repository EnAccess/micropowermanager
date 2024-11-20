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
class ManufacturerService implements IBaseService
{
    public function __construct(
        private Manufacturer $manufacturer,
    ) {
    }

    public function createManufacturerDataFromRequest(Request $request): array
    {
        return [
            'name' => $request->get('name'),
            'contact_person' => $request->get('contact_person'),
            'website' => $request->get('website'),
            'api_name' => $request->get('api_name'),
        ];
    }

    public function getById(int $manufacturerId): Manufacturer
    {
        return $this->manufacturer->newQuery()->with(['address.city.country'])->findOrFail($manufacturerId);
    }

    public function create(array $manufacturerData): Manufacturer
    {
        return $this->manufacturer->newQuery()->create($manufacturerData);
    }

    public function getAll(?int $limit = null): Collection|LengthAwarePaginator
    {
        if ($limit) {
            return $this->manufacturer->newQuery()->paginate($limit);
        }

        return $this->manufacturer->newQuery()->get();
    }

    public function getByName($manufacturerName)
    {
        return $this->manufacturer->newQuery()->where('name', $manufacturerName)->first();
    }

    public function update($model, array $data): Manufacturer
    {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool
    {
        throw new \Exception('Method delete() not yet implemented.');
    }
}
