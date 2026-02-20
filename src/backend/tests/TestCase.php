<?php

namespace Tests;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

abstract class TestCase extends BaseTestCase {
    use CreatesApplication;
    use CreateTenantCompany;

    /**
     * generates an jwt for the given user
     * if user is not present it tries to generate a token for the first user.
     */
    protected function headers(?User $user = null) {
        if (!$user instanceof User) {
            $user = User::create([
                'name' => 'John Doe',
                'email' => 'johndoe@example.com',
                'password' => '1234512345',
            ]);
        }
        $token = JWTAuth::fromUser($user);

        JWTAuth::setToken($token);

        return [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token,
        ];
    }

    public function actingAs(Authenticatable $user, $guard = null) {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");

        $guard = $driver ?? ($user instanceof Agent ? 'agent_api' : 'api');

        parent::actingAs($user, $guard);

        return $this;
    }

    public function assignRole(User $user, ...$roles): void {
        if ($roles != []) {
            $user->syncRoles($roles);
        }
    }

    public function assignPermission(User $user, ...$permissions): void {
        if ($permissions !== []) {
            $user->syncPermissions($permissions);
        }
    }

    protected function setUp(): void {
        parent::setUp();

        $this->setUpCreateTenantCompany();
        DB::connection('tenant')->beginTransaction();
    }

    protected function tearDown(): void {
        DB::connection('tenant')->rollBack();
        parent::tearDown();
    }
}
