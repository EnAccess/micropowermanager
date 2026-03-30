<?php

namespace Database\Factories\Person;

use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Person> */
class PersonFactory extends Factory {
    protected $model = Person::class;

    /**
     * Indicate that the person is a customer.
     */
    public function isCustomer(): static {
        return $this->state(function (array $attributes) {
            return [
                'is_customer' => true,
            ];
        });
    }

    /**
     * Indicate that the person is an Agent.
     */
    public function isAgent(string $village_name = 'Demo'): static {
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
     */
    public function isMaintenanceUser(string $village_name = 'Demo'): static {
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
     * @return array<string, mixed>
     */
    public function definition(): array {
        $gender = fake()->randomElement(['male', 'female']);

        return [
            'title' => fake()->title($gender),
            'education' => fake()->jobTitle(),
            'name' => fake()->firstName($gender),
            'surname' => fake()->lastName(),
            'birth_date' => fake()->date(),
            'gender' => $gender,
            'is_customer' => 0,
        ];
    }
}
