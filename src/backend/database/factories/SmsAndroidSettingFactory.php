<?php

namespace Database\Factories;

use App\Models\SmsAndroidSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class SmsAndroidSettingFactory extends Factory {
    protected $model = SmsAndroidSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'url' => $this->faker->url, // Generates a random URL
            'token' => $this->faker->regexify('[A-Za-z0-9_-]{64}'), // Mimics a token format
            'key' => $this->faker->regexify('[A-Za-z0-9:_-]{72}'), // Mimics a key format
            'callback' => sprintf(
                'https://cloud.micropowermanager.com/api/sms-android-callback/%s/confirm/11',
                $this->faker->uuid
            ), // Generates a formatted callback URL with a UUID
            'created_at' => now(), // Current timestamp
            'updated_at' => now(), // Current timestamp
        ];
    }
}
