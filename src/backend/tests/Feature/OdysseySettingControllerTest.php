<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Plugins\OdysseyDataExport\Models\OdysseySetting;
use Database\Factories\UserFactory;
use Tests\TestCase;

class OdysseySettingControllerTest extends TestCase {
    private const string URL = '/api/odyssey-data-export/settings';

    protected function setUp(): void {
        parent::setUp();

        $this->actingAs(UserFactory::new()->create());
    }

    public function testShowReturnsEmptySettingsWithoutPersisting(): void {
        $this->getJson(self::URL)
            ->assertOk()
            ->assertJsonPath('data.financing_id', null);

        $this->assertSame(0, OdysseySetting::query()->count());
    }

    public function testUpdateStoresFinancingProgram(): void {
        $this->putJson(self::URL, ['financing_id' => 'REA_NEP_PBG'])
            ->assertOk()
            ->assertJsonPath('data.financing_id', 'REA_NEP_PBG');
        $this->putJson(self::URL, ['financing_id' => null])
            ->assertOk()
            ->assertJsonPath('data.financing_id', null);

        $this->assertSame(1, OdysseySetting::query()->count());
    }

    public function testUpdateRejectsUnknownFinancingProgram(): void {
        $this->putJson(self::URL, ['financing_id' => 'UNKNOWN'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('financing_id');
    }
}
