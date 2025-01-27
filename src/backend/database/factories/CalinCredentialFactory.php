<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Inensus\CalinMeter\Models\CalinCredential;

class CalinCredentialFactory extends Factory {
    protected $model = CalinCredential::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'api_url' => 'http://api.calinhost.com/api',
            'user_id' => 'Inensus'.$this->faker->randomNumber(1, 100),
            'api_key' => '123123',
        ];
    }
}
