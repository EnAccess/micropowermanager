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
                'app_secret',
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
            'app_secret' => 'test_app_secret_1234567890abcdef',
            'transaction_confirmation_template' => 'energy_token_confirmation',
            'reminder_template' => 'tariff_debt_reminder',
        ];

        $response = $this->actingAs($this->user)->putJson('/api/whatsapp-business-api/credentials', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => 1,
                'access_token' => '••••••••_123',
                'phone_number_id' => '10987654321',
                'waba_id' => '98765432109',
                'webhook_secret' => '••••••••oken',
                'app_secret' => '••••••••cdef',
                'transaction_confirmation_template' => 'energy_token_confirmation',
                'reminder_template' => 'tariff_debt_reminder',
            ],
        ]);

        // Verify that credential fields are encrypted in the database
        $record = WhatsappCredential::query()->find(1);
        $this->assertNotNull($record);
        $this->assertNotEquals('EAAG_NEW_SYSTEM_USER_TOKEN_123', $record->getRawOriginal('access_token'));
    }

    public function testPreservesExistingSecretsWhenMaskedPlaceholdersSent(): void {
        // Initial setup
        $this->actingAs($this->user)->putJson('/api/whatsapp-business-api/credentials', [
            'id' => 1,
            'access_token' => 'ORIGINAL_ACCESS_TOKEN_ABC123',
            'phone_number_id' => '10987654321',
            'app_secret' => 'ORIGINAL_APP_SECRET_XYZ789',
        ])->assertStatus(200);

        // Update other fields while passing masked placeholders for secrets
        $response = $this->actingAs($this->user)->putJson('/api/whatsapp-business-api/credentials', [
            'id' => 1,
            'access_token' => '••••••••C123',
            'app_secret' => '••••••••Z789',
            'phone_number_id' => '99988877766',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.phone_number_id', '99988877766');

        // Confirm database still contains original decrypted secrets
        /** @var \App\Plugins\WhatsappBusinessApi\Services\WhatsappCredentialService $service */
        $service = app(\App\Plugins\WhatsappBusinessApi\Services\WhatsappCredentialService::class);
        $credentials = $service->getCredentials();
        $this->assertSame('ORIGINAL_ACCESS_TOKEN_ABC123', $credentials->access_token);
        $this->assertSame('ORIGINAL_APP_SECRET_XYZ789', $credentials->app_secret);
    }

    public function testRejectsUpdateWithoutId(): void {
        $response = $this->actingAs($this->user)->putJson('/api/whatsapp-business-api/credentials', [
            'access_token' => 'invalid-no-id',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['id']);
    }
}
