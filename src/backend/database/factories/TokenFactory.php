<?php

namespace Database\Factories;

use App\Models\Token;
use Illuminate\Database\Eloquent\Factories\Factory;

class TokenFactory extends Factory {
    protected $model = Token::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'transaction_id' => $this->faker->randomNumber(3),
            'token' => $this->generateToken(),
            'token_type' => Token::TYPE_ENERGY,
            'token_unit' => Token::UNIT_KWH,
            'token_amount' => 0.123,
        ];
    }

    /**
     * Generate a random 12-digit token.
     *
     * @return string
     */
    public static function generateToken() {
        $token = '';
        for ($i = 0; $i < 12; ++$i) {
            $token .= random_int(0, 9);
        }

        return $token;
    }
}
