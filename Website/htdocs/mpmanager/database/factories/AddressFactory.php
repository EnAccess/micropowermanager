<?php

namespace Database\Factories;

use App\Models\Address\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    protected $model = Address::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'owner_type' => 'person',
            'owner_id' => 1,
            'email' => $this->faker->email,
            'phone' => $this->faker->phoneNumber,
            'street' => $this->faker->streetAddress,
            'city_id' => 1,
            'geo_id' => null,
            'is_primary' => 1,
        ];
    }
}
