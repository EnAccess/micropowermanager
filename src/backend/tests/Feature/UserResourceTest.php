<?php

namespace Tests\Feature;

use App\Models\DatabaseProxy;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class UserResourceTest extends TestCase {
    use RefreshMultipleDatabases;
    use WithFaker;

    public function testListRegisteredUsers(): void {
        $user = UserFactory::new()->create();
        // create random users
        UserFactory::times(30)->create();

        $this->assignRole($user, 'admin');

        $response = $this->actingAs($user)->get('/api/users');
        $response->assertStatus(200);
        $this->assertEquals($response->json()['meta']['total'], 31);
    }

    public function testCreateUser(): void {
        $this->withoutExceptionHandling();
        $user = UserFactory::new()->create();
        $this->assignRole($user, 'admin');
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

    public function testUpdateUserPassword(): void {
        $user = UserFactory::new()->create();
        $this->assignRole($user, 'admin');

        // create user
        $this->actingAs($user)->post('/api/users', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => '1234123123',
        ]);

        $user = User::query()->get()[1];

        $this->assignRole($user, 'admin');

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

    public function testResetUserPassword(): void {
        $user = UserFactory::new()->create();

        $this->assignRole($user, 'admin');

        // create user
        $this->actingAs($user)->post('/api/users', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => '1234123123',
        ]);
        $user = User::query()->get()[1];
        $response = $this->actingAs($user)->post('/api/users/password', ['email' => $user->email]);
        User::query()->get()[1];

        // reset user password email was sent
        $response->assertStatus(200);

        $this->assertEquals($response->json()['data']['message'], 'If the email exists, a reset link has been sent.');
    }

    public function testAdminCanDeleteRegularUser(): void {
        $admin = UserFactory::new()->create();
        $this->assignRole($admin, 'admin');

        $target = UserFactory::new()->create();
        $this->assignRole($target, 'user');

        $response = $this->actingAs($admin)->delete('/api/users/'.$target->id);

        $response->assertStatus(200);
        $this->assertNull(User::query()->find($target->id));
    }

    public function testAdminCannotDeleteOwner(): void {
        $admin = UserFactory::new()->create();
        $this->assignRole($admin, 'admin');

        $owner = UserFactory::new()->create();
        $this->assignRole($owner, 'owner');

        $response = $this->actingAs($admin)->delete('/api/users/'.$owner->id);

        $response->assertStatus(403);
        $this->assertNotNull(User::query()->find($owner->id));
    }

    public function testOwnerCanDeleteAdmin(): void {
        $owner = UserFactory::new()->create();
        $this->assignRole($owner, 'owner');

        $admin = UserFactory::new()->create();
        $this->assignRole($admin, 'admin');

        $response = $this->actingAs($owner)->delete('/api/users/'.$admin->id);

        $response->assertStatus(200);
        $this->assertNull(User::query()->find($admin->id));
    }

    public function testUserCannotDeleteSelf(): void {
        $admin = UserFactory::new()->create();
        $this->assignRole($admin, 'admin');

        $response = $this->actingAs($admin)->delete('/api/users/'.$admin->id);

        $response->assertStatus(403);
        $this->assertNotNull(User::query()->find($admin->id));
    }

    public function testDeleteRemovesDatabaseProxyRow(): void {
        $admin = UserFactory::new()->create();
        $this->assignRole($admin, 'admin');

        $this->actingAs($admin)->post('/api/users', [
            'name' => 'ToRemove',
            'email' => 'toremove@example.com',
            'password' => 'secretsecret',
        ])->assertStatus(200);

        $target = User::query()->where('email', 'toremove@example.com')->firstOrFail();
        $this->assertNotNull(
            DatabaseProxy::query()->where('email', $target->email)->first()
        );

        $this->actingAs($admin)->delete('/api/users/'.$target->id)->assertStatus(200);

        $this->assertNull(User::query()->find($target->id));
        $this->assertNull(
            DatabaseProxy::query()->where('email', 'toremove@example.com')->first()
        );
    }

    public function testResetPasswordWithNonExistingEmail(): void {
        $request = $this->post('/api/users/password', ['email' => 'ako@inensus.com']);
        // does not indicate an error for security concerns
        $request->assertStatus(200);
        $request->assertJson([
            'data' => [
                'message' => 'If the email exists, a reset link has been sent.',
                'status_code' => 200,
            ],
        ]);
    }
}
