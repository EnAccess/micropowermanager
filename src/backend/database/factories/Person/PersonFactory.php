<?php

namespace Database\Factories\Person;

use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonFactory extends Factory {
    protected $model = Person::class;

    /**
     * Indicate that the person is a customer.
     *
     * @return Factory
     */
    public function isCustomer() {
        return $this->state(function (array $attributes) {
            return [
                'is_customer' => true,
            ];
        });
    }

    /**
     * Indicate that the person is an Agent.
     *
     * @return Factory
     */
    public function isAgent($village_name = 'Demo') {
        return $this->state(function (array $attributes) use ($village_name) {
            return [
                'is_customer' => false,
                'education' => 'MicroPowerManager Agent',
                'surname' => $attributes['surname'].' (Agent - '.$village_name.')',
            ];
        });
    }

    /**
     * Indicate that the person is an non-Agent, Maintenance User.
     *
     * @return Factory
     */
    public function isMaintenanceUser($village_name = 'Demo') {
        return $this->state(function (array $attributes) use ($village_name) {
            return [
                'is_customer' => false,
                'education' => 'MicroPowerManager Maintenance User',
                'surname' => $attributes['surname'].' (Maintenance User - '.$village_name.')',
            ];
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array {
        $sex = fake()->randomKey(['male', 'female']);
        $gender = $sex === 0
            ? 'male'
            : 'female';

        return [
            'title' => fake()->title($gender),
            'education' => fake()->jobTitle(),
            'name' => fake()->firstName($gender),
            'surname' => fake()->lastName(),
            'birth_date' => fake()->date(),
            'sex' => $sex,
            'is_customer' => 0,
        ];
    }
}
