<?php

namespace Database\Factories\Person;

use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonFactory extends Factory
{
    protected $model = Person::class;

    /**
     * Indicate that the person is a custoker.
     *
     * @return Factory
     */
    public function isCustomer()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_customer' => true,
            ];
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        $sex = fake()->randomKey(['male', 'female']);
        $gender = $sex === 0
             ? 'male'
             : 'female';

        return [
            'title' => fake()->title($gender),
            'name' => fake()->firstName($gender),
            'surname' => fake()->lastName(),
            'birth_date' => fake()->date(),
            'sex' => $sex,
            'is_customer' => 0,
        ];
    }
}
