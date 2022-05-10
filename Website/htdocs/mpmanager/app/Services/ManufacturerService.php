<?php

namespace App\Services;

use App\Models\Manufacturer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;

class ManufacturerService extends BaseService
{

    public function __construct(private Manufacturer $manufacturer)
    {
        parent::__construct([$manufacturer]);
    }

    public function getManufacturers($limit = null): Collection|LengthAwarePaginator|array
    {
        if ($limit) {
            return $this->manufacturer->newQuery()->paginate($limit);
        }
        return $this->manufacturer->newQuery()->get();
    }

    public function getById($manufacturerId): model|builder
    {
        return $this->manufacturer->newQuery()->with(['address.city.country'])->findOrFail($manufacturerId);
    }

    public function create($manufacturerData)
    {
        return $this->manufacturer->newQuery()->create($manufacturerData);
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
}