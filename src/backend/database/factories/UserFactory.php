<?php

namespace Database\Factories;

use App\Models\User;
use App\Utils\DemoCompany;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory {
    protected $model = User::class;

    /**
     * Indicate that the user is a cluster admin.
     *
     * @return Factory
     */
    public function clusterAdmin() {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Cluster Admin',
            ];
        });
    }

    public function definition() {
        return [
            'name' => $this->faker->name,
            'company_id' => DemoCompany::DEMO_COMPANY_ID,
            'email' => $this->faker->unique()->safeEmail,
            'password' => Hash::make($this->faker->password()),
            'remember_token' => str_random(10),
        ];
    }
}
