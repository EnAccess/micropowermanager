<?php


namespace App\Services;

use App\Models\Address\Address;
use App\Models\Address\HasAddressesInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AddressesService extends BaseService implements IBaseService,IAssociative
{

    public function __construct(private Address $address)
    {
        parent::__construct([$address]);
    }

    // fills the object and returns it without saving.
    public function instantiate(array $params): Address
    {
        return $this->address->fill([
            'city_id' => $params['city_id'] ?? null,
            'email' => $params['email'] ?? null,
            'phone' => $params['phone'],
            'street' => $params['street'] ?? null,
            'is_primary' => $params['is_primary'] ?? null
        ]);
    }

    public function assignAddressToOwner(HasAddressesInterface $owner, Address $address)
    {
        return $owner->addresses()->save($address);
    }

    public function getStoredAddressWithCityRelation(int $id): Address
    {
        return $this->address::with('city')->findOrFail($id);
    }

    public function createAddressDataFromRequest(Request $request): array
    {
        return [
            'email' => $request->get('email') ?? '',
            'phone' => $request->get('phone') ?? '',
            'street' => $request->get('street') ?? '',
            'city_id' => $request->get('city_id') ?? '',
            'is_primary' => $request->get('is_primary') ?? 1,
        ];
    }

    public function getById($id)
    {
        return $this->address->newQuery()->findOrFail($id);
    }

    public function getAll($limit = null)
    {
        if ($limit) {
            return $this->address->newQuery()->paginate($limit);
        }
        return $this->address->newQuery()->get();
    }

    public function create($data)
    {
        // TODO: Implement create() method.
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }

    public function update($address, $addressData)
    {
        $address->update($addressData);

        return $address;
    }

    public function make($addressData)
    {
        return $this->address->newQuery()->make([
            'email' => $addressData['email'] ?? null,
            'phone' => $addressData['phone'] ?? null,
            'street' => $addressData['street'] ?? null,
            'city_id' => $addressData['city_id'] ?? null,
            'geo_id' => $addressData['geo_id'] ?? null,
            'is_primary' => $addressData['is_primary'] ?? 0,
        ]);
    }

    public function save($address)
    {
        return $address->save();
    }
}
