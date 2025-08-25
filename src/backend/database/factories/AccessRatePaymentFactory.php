<?php

namespace Database\Factories;

use App\Models\AccessRate\AccessRatePayment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AccessRatePayment> */
class AccessRatePaymentFactory extends Factory {
    protected $model = AccessRatePayment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'debt' => 0,
            'due_date' => Carbon::now()->addDays(7),
        ];
    }
}
