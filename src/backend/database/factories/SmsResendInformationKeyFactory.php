<?php

namespace Database\Factories;

use App\Models\SmsResendInformationKey;
use Illuminate\Database\Eloquent\Factories\Factory;

class SmsResendInformationKeyFactory extends Factory {
    protected $model = SmsResendInformationKey::class;

    public function definition() {
        return [
            'key' => 'Resend',
        ];
    }
}
