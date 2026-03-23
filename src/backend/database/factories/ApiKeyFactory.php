<?php

namespace Database\Factories;

use App\Models\ApiKey;
use App\Utils\DemoCompany;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ApiKey> */
class ApiKeyFactory extends Factory {
    protected $model = ApiKey::class;

    /**
     * Indicate Api Key is *the* Demo Api Key.
     * Careful: Running this seeder twice will cause `Integrity constraint violation`.
     */
    public function isDemoApiKey(): static {
        return $this->state(function (array $attributes) {
            return [
                'token_hash' => hash('sha256', DemoCompany::DEMO_COMPANY_API_KEY),
            ];
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        $plaintext = Str::random(40).bin2hex(random_bytes(16));

        return [
            'name' => 'API KEY '.DemoCompany::DEMO_COMPANY_NAME,
            'company_id' => DemoCompany::DEMO_COMPANY_ID,
            'token_hash' => $hash = hash('sha256', $plaintext),
        ];
    }
}
