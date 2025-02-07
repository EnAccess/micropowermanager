<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyCredential;

class WaveMoneyCredentialFactory extends Factory {
    protected $model = WaveMoneyCredential::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'merchant_id' => 'MERCHANT ID '.$this->faker->randomNumber(1, 100),
            'merchant_name' => 'Demo Merchant',
            'secret_key' => '123123',
            'callback_url' => 'https://staging.micropowermanager.com/api/wave-money/wave-money-transaction/callback/11',
            'payment_url' => 'https://staging.micropowermanager.com/api/wave-money/payment/Demo Merchant/11',
            'result_url' => 'https://staging.micropowermanager.com/api/wave-money/wave-money-transaction/result/Demo Merchant/11',
        ];
    }
}
