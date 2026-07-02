<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Factories\UserFactory;
use Tests\TestCase;

class UserPermissionImportControllerTest extends TestCase {
    public function testImportCreatesAUserWithARoleAndItsPermissions(): void {
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'users');

        $response = $this->actingAs($user)->postJson('/api/import/user-permissions', [
            'data' => [[
                'name' => 'Imported User',
                'email' => 'imported.user@example.org',
                'password' => 'imported-secret-1',
                'roles' => [['name' => 'imported-role', 'permissions' => ['customers']]],
            ]],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.success', true);
        $response->assertJsonPath('data.added_count', 1);

        $importedUser = User::query()->where('email', 'imported.user@example.org')->first();
        $this->assertNotNull($importedUser);
        $this->assertTrue($importedUser->hasRole('imported-role'));
    }
}
