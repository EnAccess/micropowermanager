<?php

namespace App\Plugins\SparkMeter\Tests\Feature;

use App\Models\MpmPlugin;
use App\Models\Plugins;
use App\Plugins\SparkMeter\Jobs\SendSparkMeterSmsNotifications;
use App\Plugins\SparkMeter\Models\SmCredential;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SparkMeterSmsNotifierTest extends TestCase {
    public function testItDispatchesTheJobWhenCredentialsAreAuthenticated(): void {
        $this->preparePlugin(Plugins::ACTIVE, true);

        $this->artisan('spark-meter:smsNotifier', ['--company-id' => 1])->assertSuccessful();

        Queue::assertPushed(SendSparkMeterSmsNotifications::class, 1);
    }

    public function testItDispatchesNothingWhenCredentialsAreNotAuthenticated(): void {
        $this->preparePlugin(Plugins::ACTIVE, false);

        $this->artisan('spark-meter:smsNotifier', ['--company-id' => 1])->assertSuccessful();

        Queue::assertNotPushed(SendSparkMeterSmsNotifications::class);
    }

    public function testItDispatchesNothingWhenThePluginIsInactive(): void {
        $this->preparePlugin(Plugins::INACTIVE, true);

        $this->artisan('spark-meter:smsNotifier', ['--company-id' => 1])->assertSuccessful();

        Queue::assertNotPushed(SendSparkMeterSmsNotifications::class);
    }

    private function preparePlugin(int $status, bool $isAuthenticated): void {
        Queue::fake();
        UserFactory::new()->create();
        Plugins::query()->create([
            'mpm_plugin_id' => MpmPlugin::SPARK_METER,
            'status' => $status,
        ]);
        SmCredential::query()->create([
            'api_url' => 'https://www.sparkmeter.cloud/api/v0',
            'is_authenticated' => $isAuthenticated,
        ]);
    }
}
