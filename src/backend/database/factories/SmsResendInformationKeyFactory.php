<?php

namespace Database\Factories;

use App\Models\SmsResendInformationKey;
use Illuminate\Database\Eloquent\Factories\Factory;

class SmsResendInformationKeyFactory extends Factory {
    protected $model = SmsResendInformationKey::class;

    public function definition() {
        return [
            'id' => $this->faker->numberBetween(1, 10),
            'key' => 'Resend',
        ];
    }
}
