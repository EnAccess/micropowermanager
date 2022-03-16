<?php

namespace Database\Factories;

use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

class PersonFactory extends Factory
{
    protected $model = Person::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->title('male'),
            'name' => $this->faker->firstName(),
            'surname' => $this->faker->firstName(),
            'birth_date' => $this->faker->date(),
            'sex' => $this->faker->randomKey(['male', 'female']),
            'is_customer' => 1,
        ];
    }
}
