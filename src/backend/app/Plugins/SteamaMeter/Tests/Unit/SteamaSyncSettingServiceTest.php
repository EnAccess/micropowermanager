<?php

namespace App\Plugins\SteamaMeter\Tests\Unit;

use App\Plugins\SteamaMeter\Models\SteamaSyncAction;
use App\Plugins\SteamaMeter\Models\SteamaSyncSetting;
use App\Plugins\SteamaMeter\Services\SteamaSyncSettingService;
use Carbon\Carbon;
use Tests\TestCase;

class SteamaSyncSettingServiceTest extends TestCase {
    private const ACTION_NAMES = ['Sites', 'Customers', 'Meters', 'Agents', 'Transactions'];

    private function service(): SteamaSyncSettingService {
        return app(SteamaSyncSettingService::class);
    }

    public function testCreateDefaultSettingsSeedsAnImmediatelyDueActionForEverySetting(): void {
        $this->service()->createDefaultSettings();

        $this->assertEqualsCanonicalizing(
            self::ACTION_NAMES,
            SteamaSyncSetting::query()->pluck('action_name')->all()
        );
        $this->assertCount(count(self::ACTION_NAMES), SteamaSyncAction::query()->get());
        $this->assertTrue(
            SteamaSyncAction::query()->where('next_sync', '>', Carbon::now())->doesntExist(),
            'Seeded actions should be due immediately so the next dataSync run dispatches them.'
        );
    }

    public function testCreateDefaultSettingsBackfillsMissingActionForAPreExistingSetting(): void {
        $orphanSetting = SteamaSyncSetting::query()->create([
            'action_name' => 'Sites',
            'sync_in_value_str' => 'day',
            'sync_in_value_num' => 1,
        ]);

        $this->service()->createDefaultSettings();

        $this->assertNotNull(
            SteamaSyncAction::query()->where('sync_setting_id', $orphanSetting->id)->first()
        );
        $this->assertEquals(1, SteamaSyncSetting::query()->where('action_name', 'Sites')->count());
        $this->assertCount(count(self::ACTION_NAMES), SteamaSyncAction::query()->get());
    }

    public function testCreateDefaultSettingsIsIdempotent(): void {
        $this->service()->createDefaultSettings();
        $this->service()->createDefaultSettings();

        $this->assertCount(count(self::ACTION_NAMES), SteamaSyncSetting::query()->get());
        $this->assertCount(count(self::ACTION_NAMES), SteamaSyncAction::query()->get());
    }
}
