<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class CompanyFactory extends Factory {
    protected $model = Company::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'name' => $this->faker->company,
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
            'email' => $this->faker->email,
            'country_id' => 1,
        ];
    }

    public function createWithEmail(string $email): Model {
        $base = $this->definition();
        $base['email'] = $email;

        return $this->create($base);
    }
}
