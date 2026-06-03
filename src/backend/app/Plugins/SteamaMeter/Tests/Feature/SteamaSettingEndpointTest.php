<?php

namespace App\Plugins\SteamaMeter\Tests\Feature;

use App\Plugins\SteamaMeter\Models\SteamaSetting;
use App\Plugins\SteamaMeter\Models\SteamaSyncSetting;
use Database\Factories\UserFactory;
use Tests\TestCase;

class SteamaSettingEndpointTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        $this->actingAs(UserFactory::new()->create());
    }

    public function testIndexReturnsEachSettingWithItsMorphTargetLoaded(): void {
        $syncSetting = SteamaSyncSetting::query()->create([
            'action_name' => 'Sites',
            'sync_in_value_str' => 'day',
            'sync_in_value_num' => 1,
        ]);
        $setting = new SteamaSetting();
        $setting->setting()->associate($syncSetting);
        $setting->save();

        $response = $this->getJson('/api/steama-meters/steama-setting')->assertOk();

        $response->assertJsonPath('data.0.setting_type', 'steama_sync_setting');
        $response->assertJsonPath('data.0.setting.action_name', 'Sites');
    }
}
