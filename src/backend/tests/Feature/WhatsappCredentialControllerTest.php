<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappCredential;
use Database\Factories\UserFactory;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class WhatsappCredentialControllerTest extends TestCase {
    use RefreshMultipleDatabases;

    private User $user;

    protected function setUp(): void {
        parent::setUp();
        $this->user = UserFactory::new()->create(['company_id' => $this->companyId]);
    }

    public function testFetchesStoredOrNewCredentials(): void {
        $response = $this->actingAs($this->user)->getJson('/api/whatsapp-business-api/credentials');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'access_token',
                'phone_number_id',
                'waba_id',
                'webhook_secret',
                'transaction_confirmation_template',
                'reminder_template',
            ],
        ]);
    }

    public function testUpdatesCredentialsSuccessfully(): void {
        $payload = [
            'id' => 1,
            'access_token' => 'EAAG_NEW_SYSTEM_USER_TOKEN_123',
            'phone_number_id' => '10987654321',
            'waba_id' => '98765432109',
            'webhook_secret' => 'super_secret_webhook_verify_token',
            'transaction_confirmation_template' => 'energy_token_confirmation',
            'reminder_template' => 'tariff_debt_reminder',
        ];

        $response = $this->actingAs($this->user)->putJson('/api/whatsapp-business-api/credentials', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => 1,
                'access_token' => 'EAAG_NEW_SYSTEM_USER_TOKEN_123',
                'phone_number_id' => '10987654321',
                'waba_id' => '98765432109',
                'webhook_secret' => 'super_secret_webhook_verify_token',
                'transaction_confirmation_template' => 'energy_token_confirmation',
                'reminder_template' => 'tariff_debt_reminder',
            ],
        ]);

        // Verify that credential fields are encrypted in the database
        $record = WhatsappCredential::query()->find(1);
        $this->assertNotNull($record);
        $this->assertNotEquals('EAAG_NEW_SYSTEM_USER_TOKEN_123', $record->getRawOriginal('access_token'));
    }

    public function testRejectsUpdateWithoutId(): void {
        $response = $this->actingAs($this->user)->putJson('/api/whatsapp-business-api/credentials', [
            'access_token' => 'invalid-no-id',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['id']);
    }
}
