<?php

namespace App\Plugins\SteamaMeter\Tests\Feature;

use App\Models\Plugins;
use App\Plugins\SteamaMeter\Console\Commands\SteamaMeterDataSynchronizer;
use App\Plugins\SteamaMeter\Jobs\SyncSteamaData;
use App\Plugins\SteamaMeter\Models\SteamaSyncAction;
use App\Plugins\SteamaMeter\Models\SteamaSyncSetting;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SteamaMeterDataSynchronizerTest extends TestCase {
    public function testResourceSyncsStayDueUntilTheJobSucceedsWhileTransactionsAdvanceOnDispatch(): void {
        Queue::fake();
        UserFactory::new()->create();
        Plugins::query()->create([
            'mpm_plugin_id' => SteamaMeterDataSynchronizer::MPM_PLUGIN_ID,
            'status' => Plugins::ACTIVE,
        ]);

        $sites = $this->dueAction('Sites', 'day', 1);
        $transactions = $this->dueAction('Transactions', 'minute', 5);

        $this->artisan('steama-meter:dataSync', ['--company-id' => 1])->assertSuccessful();

        Queue::assertPushed(SyncSteamaData::class, 2);
        $this->assertTrue(
            Carbon::parse($sites->fresh()->next_sync)->isPast(),
            'A resource sync must stay due (next_sync unchanged) until its job writes to the DB successfully.'
        );
        $this->assertTrue(
            Carbon::parse($transactions->fresh()->next_sync)->isFuture(),
            'Transactions advances its schedule on dispatch.'
        );
    }

    private function dueAction(string $actionName, string $intervalStr, int $intervalNum): SteamaSyncAction {
        $setting = SteamaSyncSetting::query()->create([
            'action_name' => $actionName,
            'sync_in_value_str' => $intervalStr,
            'sync_in_value_num' => $intervalNum,
        ]);

        return SteamaSyncAction::query()->create([
            'sync_setting_id' => $setting->id,
            'next_sync' => Carbon::now()->subDay(),
            'attempts' => 0,
        ]);
    }
}
