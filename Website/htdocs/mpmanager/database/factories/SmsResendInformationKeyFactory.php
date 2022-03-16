<?php

namespace Database\Factories;

use App\Models\SmsResendInformationKey;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;

class SmsResendInformationKeyFactory extends Factory
{
    protected $model = SmsResendInformationKey::class;

    public function definition()
    {
        return [
            'key' => 'Resend'
        ];
    }
}
