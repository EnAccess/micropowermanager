<?php

namespace App\Services;

use App\Models\Manufacturer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;

class ManufacturerService  implements IBaseService
{

    public function __construct(private Manufacturer $manufacturer)
    {

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

    public function getById($manufacturerId)
    {
        return $this->manufacturer->newQuery()->with(['address.city.country'])->findOrFail($manufacturerId);
    }

    public function create($manufacturerData)
    {
        return $this->manufacturer->newQuery()->create($manufacturerData);
    }

    public function getAll($limit = null)
    {
        if ($limit) {
            return $this->manufacturer->newQuery()->paginate($limit);
        }
        return $this->manufacturer->newQuery()->get();
    }

    public function update($model, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }
}
