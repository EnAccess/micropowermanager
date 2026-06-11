<?php

namespace App\Plugins\SteamaMeter\Tests\Unit;

use App\Plugins\SteamaMeter\Models\SteamaSyncAction;
use App\Plugins\SteamaMeter\Models\SteamaSyncSetting;
use App\Plugins\SteamaMeter\Services\SteamaSyncActionService;
use Carbon\Carbon;
use Tests\TestCase;

class SteamaSyncActionServiceTest extends TestCase {
    private SteamaSyncSetting $setting;
    private SteamaSyncAction $action;

    protected function setUp(): void {
        parent::setUp();

        $this->setting = SteamaSyncSetting::query()->create([
            'action_name' => 'Sites',
            'sync_in_value_str' => 'day',
            'sync_in_value_num' => 1,
        ]);
        $this->action = SteamaSyncAction::query()->create([
            'sync_setting_id' => $this->setting->id,
            'attempts' => 2,
            'next_sync' => Carbon::now()->subDay(),
        ]);
        $this->action->refresh();
    }

    private function service(): SteamaSyncActionService {
        return resolve(SteamaSyncActionService::class);
    }

    public function testFailedSyncIncrementsAttemptsWithoutAdvancingSchedule(): void {
        $originalNextSync = $this->action->next_sync;

        $this->service()->updateSyncAction($this->action, $this->setting, false);
        $this->action->refresh();

        $this->assertEquals(3, $this->action->attempts);
        $this->assertEquals($originalNextSync, $this->action->next_sync);
    }

    public function testSuccessfulSyncResetsAttemptsAndAdvancesSchedule(): void {
        $this->service()->updateSyncAction($this->action, $this->setting, true);
        $this->action->refresh();

        $this->assertEquals(0, $this->action->attempts);
        $this->assertTrue(Carbon::parse($this->action->next_sync)->isFuture());
    }

    public function testScheduleNextRunAdvancesScheduleButKeepsAttempts(): void {
        $this->service()->scheduleNextRun($this->action, $this->setting);
        $this->action->refresh();

        $this->assertEquals(2, $this->action->attempts);
        $this->assertTrue(Carbon::parse($this->action->next_sync)->isFuture());
    }

    public function testGetActionsNeedsToSyncReturnsOnlyDueActions(): void {
        $futureSetting = SteamaSyncSetting::query()->create([
            'action_name' => 'Customers',
            'sync_in_value_str' => 'day',
            'sync_in_value_num' => 1,
        ]);
        $futureAction = SteamaSyncAction::query()->create([
            'sync_setting_id' => $futureSetting->id,
            'attempts' => 0,
            'next_sync' => Carbon::now()->addDay(),
        ]);

        $dueActionIds = $this->service()->getActionsNeedsToSync()->pluck('id');

        $this->assertTrue($dueActionIds->contains($this->action->id));
        $this->assertFalse($dueActionIds->contains($futureAction->id));
    }

    public function testGetSyncActionBySynSettingIdReturnsTheMatchingAction(): void {
        $found = $this->service()->getSyncActionBySynSettingId($this->setting->id);

        $this->assertNotNull($found);
        $this->assertEquals($this->action->id, $found->id);
    }
}
