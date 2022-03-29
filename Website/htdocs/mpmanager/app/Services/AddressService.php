<?php


namespace App\Services;

use App\Models\Address\Address;
use App\Models\Address\HasAddressesInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AddressService
{
    /**
     * @var Address
     */
    private $address;

    public function __construct(Address $address)
    {
        $this->address = $address;
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

    /**
     * @return Model|false
     */
    public function assignAddressToOwner(HasAddressesInterface $owner, Address $address)
    {
        return $owner->addresses()->save($address);
    }
}
