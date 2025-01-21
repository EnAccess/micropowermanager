<?php

namespace Database\Factories;

use App\Models\Sms;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SmsFactory extends Factory {
    protected $model = Sms::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        // Generate realistic Tanzanian names
        $firstNames = ['Ambidwile', 'Samiha', 'Tatu', 'Sharifa', 'Manica', 'Seghen', 'Bamba',
            'Bora', 'Hadiya', 'Fahima', 'Asiya', 'Asha', 'Adla', 'Hiba', 'Malika'];
        $lastNames = ['Ngabile', 'Furaha', 'Amaziah', 'Sanaa', 'Asili', 'Adhra', 'Fadhili',
            'Buyu', 'Sakina', 'Tumo', 'Ndweleifwa', 'Buyu', 'Amaziah', 'Saidi'];

        // Generate realistic meter numbers
        $meterNumber = '47000'.$this->faker->numberBetween(290000, 520000);

        // Generate realistic transaction amounts (between 1000 and 20000)
        $amount = $this->faker->numberBetween(1000, 20000);

        // Calculate units based on amount (approximately amount/2)
        $units = round($amount / 2, 1);

        // Calculate VAT (1% of amount)
        $vat = round($amount / 100, 2);

        // Generate random token (32 characters)
        $token = Str::random(32);

        // Format Tanzanian phone number
        $phone = '+255'.$this->faker->numberBetween(710000000, 789999999);

        // Construct message body
        $fullName = $this->faker->firstName().' '.$this->faker->lastName();
        $body = sprintf(
            'Dear %s, we received your transaction %d.Meter: %s, %s Unit %s .Transaction amount is %d, \n VAT for energy : %s \n VAT for the other staffs : 0 . Your Company etc.',
            $fullName,
            $amount,
            $meterNumber,
            $token,
            $units,
            $amount,
            $vat
        );

        return [
            'receiver' => $phone,
            'trigger_type' => 0,
            'trigger_id' => $this->faker->unique()->numberBetween(1, 10000),
            'body' => $body,
            'status' => 0,
            'uuid' => $this->faker->uuid(),
            'sender_id' => null,
            'direction' => 1,
            'gateway_id' => 1,
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
