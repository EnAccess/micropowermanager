<?php

namespace App\Plugins\SteamaMeter\Tests\Unit;

use App\Plugins\SteamaMeter\Jobs\SyncSteamaData;
use Illuminate\Queue\Middleware\WithoutOverlapping;
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

    public function testIsGuardedAgainstOverlappingRunsPerActionAndCompany(): void {
        $middleware = new SyncSteamaData('Sites', 7)->middleware();

        $this->assertCount(1, $middleware);
        $overlap = $middleware[0];
        $this->assertInstanceOf(WithoutOverlapping::class, $overlap);
        $this->assertSame('Sites-7', $overlap->key, 'Lock must be scoped per action and tenant.');
        $this->assertNull($overlap->releaseAfter, 'Overlapping dispatches must be dropped, not requeued.');
        $this->assertSame(600, $overlap->expiresAfter, 'Lock must auto-expire so a dead worker cannot block future runs.');
    }

    public function testAnActionForADifferentCompanyUsesADistinctLockKey(): void {
        $this->assertNotSame(
            new SyncSteamaData('Sites', 1)->middleware()[0]->key,
            new SyncSteamaData('Sites', 2)->middleware()[0]->key,
        );
    }
}
