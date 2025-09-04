<?php

namespace App\Services;

use App\Models\Address\Address;
use App\Models\Address\HasAddressesInterface;
use App\Services\Interfaces\IAssociative;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<Address>
 * @implements IAssociative<Address>
 */
class AddressesService implements IBaseService, IAssociative {
    public function __construct(
        private Address $address,
    ) {}

    /**
     * @param array<string, mixed> $params
     */
    public function instantiate(array $params): Address {
        return $this->address->fill([
            'city_id' => $params['city_id'] ?? null,
            'email' => $params['email'] ?? null,
            'phone' => $params['phone'],
            'street' => $params['street'] ?? null,
            'is_primary' => $params['is_primary'] ?? null,
        ]);
    }

    public function assignAddressToOwner(HasAddressesInterface $owner, Address $address): Address|bool {
        return $owner->addresses()->save($address);
    }

    public function getStoredAddressWithCityRelation(int $id): Address {
        return $this->address::with('city')->findOrFail($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function createAddressDataFromRequest(Request $request): array {
        return [
            'email' => $request->get('email') ?? '',
            'phone' => $request->get('phone') ?? '',
            'street' => $request->get('street') ?? '',
            'city_id' => $request->get('city_id') ?? '',
            'is_primary' => $request->get('is_primary') ?? 1,
        ];
    }

    public function getById(int $id): Address {
        return $this->address->newQuery()->findOrFail($id);
    }

    /**
     * @return Collection<int, Address>|LengthAwarePaginator<Address>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit) {
            return $this->address->newQuery()->paginate($limit);
        }

        return $this->address->newQuery()->get();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Address {
        throw new \Exception('Method create() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @param array<string, mixed> $addressData
     */
    public function update($address, array $addressData): Address {
        $address->update($addressData);

        return $address;
    }

    /**
     * @param array<string, mixed> $addressData
     */
    public function make(array $addressData): Address {
        return $this->address->newQuery()->make([
            'email' => $addressData['email'] ?? null,
            'phone' => $addressData['phone'] ?? null,
            'street' => $addressData['street'] ?? null,
            'city_id' => $addressData['city_id'] ?? null,
            'is_primary' => $addressData['is_primary'] ?? 0,
        ]);
    }

    public function save($address): bool {
        return $address->save();
    }

    public function getAddressByPhoneNumber(string $phoneNumber): ?Address {
        return $this->address->newQuery()
            ->where('phone', $phoneNumber)
            ->orWhere('phone', ltrim($phoneNumber, '+'))
            ->first();
    }
}
