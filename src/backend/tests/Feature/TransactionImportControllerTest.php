<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Factories\UserFactory;
use Tests\TestCase;

class TransactionImportControllerTest extends TestCase {
    public function testImportReportsAPerItemFailureForAnUnknownDevice(): void {
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'transactions');

        $response = $this->actingAs($user)->postJson('/api/import/transactions', [
            'data' => [['device_id' => 'NO-SUCH-DEVICE', 'amount' => '1,234.56 TZS']],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.success', false);
        $response->assertJsonPath('data.failed_count', 1);
        $response->assertJsonPath('data.failed.0.device_id', 'NO-SUCH-DEVICE');
    }
}
