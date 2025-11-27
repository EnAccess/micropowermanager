<?php

namespace Database\Factories;

use App\Events\UserCreatedEvent;
use App\Models\User;
use App\Utils\DemoCompany;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<User> */
class UserFactory extends Factory {
    protected $model = User::class;

    /**
     * Indicate that the user is a cluster admin.
     */
    public function clusterAdmin(): static {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Cluster Admin',
            ];
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'name' => $this->faker->name,
            'company_id' => DemoCompany::DEMO_COMPANY_ID,
            'email' => $this->faker->unique()->safeEmail,
            'password' => Hash::make($this->faker->password()),
            'remember_token' => str_random(10),
        ];
    }

    public function configure() {
        // add the database proxy for each users that is generated with the factory
        return $this->afterCreating(function (User $user) {
            event(new UserCreatedEvent($user, true));
        });
    }
}
