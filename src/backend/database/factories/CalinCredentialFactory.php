<?php

namespace Database\Factories;

use App\Plugins\CalinMeter\Models\CalinCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CalinCredential> */
class CalinCredentialFactory extends Factory {
    protected $model = CalinCredential::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'api_url' => 'http://api.calinhost.com/api',
            'user_id' => 'Inensus'.$this->faker->randomNumber(3),
            'api_key' => '123123',
        ];
    }
}
