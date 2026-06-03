<?php

namespace App\Plugins\SteamaMeter\Tests\Unit;

use App\Plugins\SteamaMeter\Jobs\SyncSteamaData;
use Tests\TestCase;

class SyncSteamaDataTest extends TestCase {
    public function testAllowsAtLeastFiveMinutesAndRetryAfterStaysAboveTheTimeout(): void {
        $job = new SyncSteamaData('Sites', 1);

        $this->assertGreaterThanOrEqual(300, $job->timeout);
        $this->assertGreaterThan(
            $job->timeout,
            config('queue.connections.redis.retry_after'),
            'retry_after must exceed the job timeout, or a slow still-running sync gets re-dispatched as a duplicate.'
        );
    }
}
