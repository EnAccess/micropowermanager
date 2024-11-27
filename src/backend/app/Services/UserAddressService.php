<?php

namespace App\Services;

use App\Models\Address\Address;
use App\Models\User;

class UserAddressService {
    private $address;
    private $user;

    public function __construct(Address $address, User $user) {
        $this->address = $address;
        $this->user = $user;
    }

    public function create(User $user, $data) {
        $address = $this->address->newQuery()->create([
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'street' => $data['street'] ?? '',
            'city_id' => $data['city_id'] ?? '',
        ]);
        // delete address if exists
        $user->address()->delete();
        $address->owner()->associate($user);
        $address->save();

        return $address->with(['city']);
    }

    public function update(User $user, $data) {
        $user->name = $data['name'];
        $user->update();
        $address = $user->address()->first();
        if (!$address) {
            $address = $this->address->newQuery()->create([
                'email' => $user->email,
                'phone' => $data['phone'],
                'street' => $data['street'],
                'city_id' => $data['city_id'],
                'is_primary' => 1,
            ]);
            $address->owner()->associate($user);
            $address->save();

            return $this->user->newQuery()->with(['addressDetails'])->find($user->id);
        }
        $address->update([
            'email' => $user->email,
            'phone' => $data['phone'],
            'street' => $data['street'],
            'city_id' => $data['city_id'],
            'is_primary' => $address->is_primary,
        ]);

        return $this->user->newQuery()->with(['addressDetails'])->find($user->id);
    }
}
