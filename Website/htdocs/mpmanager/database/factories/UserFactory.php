<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'company_id' => 1,
            'email' => $this->faker->unique()->safeEmail,
            'password' => Hash::make($this->faker->password()),
            'remember_token' => str_random(10),
        ];
    }
}
