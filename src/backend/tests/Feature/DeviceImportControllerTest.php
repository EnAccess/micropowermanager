<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Factories\UserFactory;
use Tests\TestCase;

class DeviceImportControllerTest extends TestCase {
    public function testImportReturnsImportResultShapeOnSuccess(): void {
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'customers');

        $response = $this->actingAs($user)->postJson('/api/import/devices', [
            'data' => [
                ['device_info' => ['serial_number' => 'DEVICE-CONTROLLER-TEST-OK', 'manufacturer' => 'Test Manufacturer']],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.success', true);
        $response->assertJsonPath('data.imported_count', 1);
        $response->assertJsonPath('data.added_count', 1);
        $response->assertJsonCount(1, 'data.added');
    }
}
