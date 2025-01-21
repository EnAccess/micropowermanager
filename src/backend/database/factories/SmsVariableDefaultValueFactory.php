<?php

namespace Database\Factories;

use App\Models\SmsVariableDefaultValue;
use Illuminate\Database\Eloquent\Factories\Factory;

class SmsVariableDefaultValueFactory extends Factory {
    protected $model = SmsVariableDefaultValue::class;

    protected $variables = [
        'name' => ['Herbert', 'John', 'Maria', 'Sarah', 'Michael'],
        'surname' => ['Kale', 'Smith', 'Johnson', 'Brown', 'Davis'],
        'amount' => ['1000', '1500', '2000', '2500', '3000'],
        'appliance_type_name' => ['fridge', 'tv', 'cooker', 'ac', 'washing machine'],
        'remaining' => ['3', '4', '5', '6', '7'],
        'due_date' => ['2021/04/01', '2021/05/01', '2021/06/01', '2021/07/01'],
        'meter' => ['47782371232', '47782371233', '47782371234', '47782371235'],
        'token' => ['5111 3511 9911 1177 7711', '5111 3511 9911 1177 7712'],
        'vat_energy' => ['15', '16', '17', '18', '19'],
        'vat_others' => ['10', '11', '12', '13', '14'],
        'energy' => ['5123.1', '5124.2', '5125.3', '5126.4'],
        'transaction_amount' => ['500', '600', '700', '800', '900'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        $variable = $this->faker->randomElement(array_keys($this->variables));

        return [
            'variable' => $variable,
            'value' => $this->faker->randomElement($this->variables[$variable]),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
