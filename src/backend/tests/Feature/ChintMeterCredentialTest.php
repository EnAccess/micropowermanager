<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Plugins\ChintMeter\Enums\TariffAppliedBy;
use App\Plugins\ChintMeter\Models\ChintCredential;
use App\Plugins\ChintMeter\Services\ChintCredentialService;
use Database\Factories\UserFactory;
use Tests\TestCase;

class ChintMeterCredentialTest extends TestCase {
    private const array CREDENTIALS = [
        'api_url' => 'https://chint.test/PrepaidWebService.asmx',
        'user_name' => 'operator',
        'user_password' => 'secret',
    ];

    protected function setUp(): void {
        parent::setUp();
        $this->app->make(ChintCredentialService::class)->createCredentials();
    }

    public function testChintAppliesTheTariffUnlessSetOtherwise(): void {
        $response = $this->actingAs(UserFactory::new()->create())
            ->putJson('/api/chint-meters/chint-credential', self::CREDENTIALS);

        $response->assertOk();
        $response->assertJsonPath('data.api_url', 'https://chint.test/PrepaidWebService.asmx');
        $response->assertJsonPath('data.tariff_applied_by', TariffAppliedBy::ChintPlatform->value);
    }

    public function testTheTariffCanBeAppliedByMpm(): void {
        $response = $this->actingAs(UserFactory::new()->create())
            ->putJson('/api/chint-meters/chint-credential', [...self::CREDENTIALS, 'tariff_applied_by' => 'mpm']);

        $response->assertOk();
        $response->assertJsonPath('data.tariff_applied_by', TariffAppliedBy::Mpm->value);
        $this->assertSame(TariffAppliedBy::Mpm, ChintCredential::query()->firstOrFail()->tariff_applied_by);
    }

    public function testAnUnknownTariffSettingIsRejected(): void {
        $response = $this->actingAs(UserFactory::new()->create())
            ->putJson('/api/chint-meters/chint-credential', [...self::CREDENTIALS, 'tariff_applied_by' => 'someone-else']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('tariff_applied_by');
    }
}
