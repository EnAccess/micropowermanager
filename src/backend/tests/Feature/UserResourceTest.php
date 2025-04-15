<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserResourceTest extends TestCase {
    use RefreshDatabase;
    use WithFaker;

    public function actingAs($user, $driver = null) {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }

    /** @test */
    public function listRegisteredUsers(): void {
        $user = UserFactory::new()->create();
        // create random users
        UserFactory::times(30)->create();

        $response = $this->actingAs($user)->get('/api/users');
        $response->assertStatus(200);
        $this->assertEquals($response->json()['total'], 31);
    }

    /** @test */
    public function createUser(): void {
        $this->withoutExceptionHandling();
        $user = UserFactory::new()->create();
        $response = $this->actingAs($user)->post('/api/users', [
            'name' => 'TestUser',
            'email' => 'test@test.com',
            'password' => '1234123123',
        ]);
        $response->assertStatus(200);
        $user = User::query()->get()[1];
        $this->assertTrue(Hash::check('1234123123', $user->password));
        $this->assertEquals($user->email, 'test@test.com');
    }

    /** @test */
    public function updateUserPassword(): void {
        $user = UserFactory::new()->create();
        // create user
        $this->actingAs($user)->post('/api/users', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => '1234123123',
        ]);

        $user = User::query()->get()[1];

        $response = $this->actingAs($user)->put(
            '/api/users/password/'.$user->id,
            [
                'id' => $user->id,
                'password' => '12345',
                'confirm_password' => '12345',
            ]
        );
        $response->assertStatus(200);
        $user = User::query()->get()[1];
        $this->assertTrue(Hash::check('12345', $user->password));
    }

    /** @test */
    public function resetUserPassword(): void {
        $user = UserFactory::new()->create();
        // create user
        $this->actingAs($user)->post('/api/users', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => '1234123123',
        ]);
        $user = User::query()->get()[1];
        $oldPassword = $user->password;
        $this->post('/api/users/password', ['email' => $user->email]);
        $userWitNewPassword = User::query()->get()[1];

        $this->assertNotEquals($oldPassword, $userWitNewPassword->password);
    }

    /** @test */
    public function resetPasswordWithNonExistingEmail(): void {
        $request = $this->post('/api/users/password', ['email' => 'ako@inensus.com']);

        $request->assertStatus(422);
    }
}
