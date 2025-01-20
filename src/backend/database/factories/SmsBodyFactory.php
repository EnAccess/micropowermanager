<?php

namespace Database\Factories;

use App\Models\SmsBody;
use Illuminate\Database\Eloquent\Factories\Factory;

class SmsBodyFactory extends Factory {
    protected $model = SmsBody::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
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

    /**
     * Define the specific data for seeding.
     *
     * @return static
     */
    public function withCustomData($data) {
        return $this->state($data);
    }
}
