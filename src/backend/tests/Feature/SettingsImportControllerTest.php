<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MainSettings;
use Database\Factories\UserFactory;
use Tests\TestCase;

class SettingsImportControllerTest extends TestCase {
    public function testImportReturnsTheEnvelopedImportResultShape(): void {
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'settings');

        $response = $this->actingAs($user)->postJson('/api/import/settings', [
            'data' => [['currency' => 'EUR', 'vat_energy' => 18]],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.success', true);
        $response->assertJsonPath('data.imported_count', 1);
        $response->assertJsonPath('data.failed_count', 0);

        $result = $response->json('data');
        $settingsRecord = $result['modified'][0] ?? $result['added'][0];
        $this->assertSame('EUR', $settingsRecord['currency']);
    }

    public function testImportStoresTheDownPaymentTokenLimit(): void {
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'settings');

        $response = $this->actingAs($user)->postJson('/api/import/settings', [
            'data' => [['down_payment_max_token_days' => 14]],
        ]);

        $response->assertStatus(200);
        $this->assertSame(14, MainSettings::query()->firstOrFail()->down_payment_max_token_days);
    }

    public function testAnImportWithoutTheDownPaymentTokenLimitLeavesItAlone(): void {
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'settings');
        MainSettings::query()->firstOrFail()->update(['down_payment_max_token_days' => 14]);

        $this->actingAs($user)->postJson('/api/import/settings', [
            'data' => [['currency' => 'EUR']],
        ])->assertStatus(200);

        $this->assertSame(14, MainSettings::query()->firstOrFail()->down_payment_max_token_days);
    }

    public function testImportRejectsABareSettingsObjectPayload(): void {
        $user = UserFactory::new()->create();
        $this->assignPermission($user, 'settings');

        $response = $this->actingAs($user)->postJson('/api/import/settings', [
            'data' => ['currency' => 'EUR'],
        ]);

        $response->assertStatus(422);
    }
}
