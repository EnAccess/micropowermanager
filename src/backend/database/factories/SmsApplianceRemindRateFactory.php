<?php

namespace Database\Factories;

use App\Models\SmsApplianceRemindRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SmsApplianceRemindRate> */
class SmsApplianceRemindRateFactory extends Factory {
    protected $model = SmsApplianceRemindRate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'appliance_id' => $this->faker->numberBetween(1, 10),
            'overdue_remind_rate' => $this->faker->numberBetween(1, 30),
            'remind_rate' => $this->faker->numberBetween(1, 30),
            'enabled' => false,
        ];
    }
}
