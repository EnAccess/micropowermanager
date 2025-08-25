<?php

namespace Database\Factories\Address;

use App\Models\Address\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Address> */
class AddressFactory extends Factory {
    protected $model = Address::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->unique()->e164PhoneNumber(),
            'street' => $this->faker->streetAddress,
            'is_primary' => 1,
        ];
    }
}
