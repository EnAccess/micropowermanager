<?php

namespace Database\Factories;

use App\Models\AccessRate\AccessRatePayment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccessRatePaymentFactory extends Factory {
    protected $model = AccessRatePayment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'debt' => 0,
            'due_date' => Carbon::now()->addDays(7),
        ];
    }
}
