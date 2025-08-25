<?php

namespace Database\Factories;

use App\Models\SmsBody;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SmsBody> */
class SmsBodyFactory extends Factory {
    protected $model = SmsBody::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'reference' => 'test ref',
            'title' => 'Test title',
            'body' => 'test body',
            'place_holder' => 'test placeholder',
            'variables' => 'variable',
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween($this->faker->dateTimeThisYear(), 'now'),
        ];
    }
}
