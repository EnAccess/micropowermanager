<?php

namespace Tests\Unit;

use App\Models\PendingJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Redis;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class FlushPendingJobsCommandTest extends TestCase {
    use RefreshMultipleDatabases;

    protected function setUp(): void {
        parent::setUp();

        PendingJob::query()->delete();
    }

    public function testFlushesBufferedJobsWhenRedisIsAvailable(): void {
        Bus::fake();

        $job = new FakeQueueJob();
        $job->queue = 'default';

        PendingJob::create([
            'queue' => 'default',
            'connection' => 'redis',
            'payload' => serialize($job),
            'delay_seconds' => 0,
        ]);

        $this->assertEquals(1, PendingJob::count());

        $this->artisan('queue:flush-pending')
            ->assertSuccessful()
            ->expectsOutputToContain('Flushed 1 pending jobs');

        $this->assertEquals(0, PendingJob::count());
    }

    public function testSkipsFlushWhenRedisIsUnavailable(): void {
        // Mock Redis to simulate unavailability
        Redis::shouldReceive('connection')
            ->andReturnUsing(function () {
                $connection = \Mockery::mock();
                $connection->shouldReceive('ping')->andThrow(new \Exception('Connection refused'));

                return $connection;
            });

        PendingJob::create([
            'queue' => 'default',
            'connection' => 'redis',
            'payload' => serialize(new FakeQueueJob()),
            'delay_seconds' => 0,
        ]);

        $this->artisan('queue:flush-pending')
            ->assertSuccessful()
            ->expectsOutputToContain('Redis is not available');

        $this->assertEquals(1, PendingJob::count());
    }

    public function testDeletesJobAfterSuccessfulDispatch(): void {
        Bus::fake();

        PendingJob::create([
            'queue' => 'payments',
            'connection' => 'redis',
            'payload' => serialize(new FakeQueueJob()),
            'delay_seconds' => 0,
        ]);

        PendingJob::create([
            'queue' => 'notifications',
            'connection' => 'redis',
            'payload' => serialize(new FakeQueueJob()),
            'delay_seconds' => 0,
        ]);

        $this->artisan('queue:flush-pending')->assertSuccessful();

        $this->assertEquals(0, PendingJob::count());
    }

    public function testIncrementsAttemptsOnFailure(): void {
        // Serialized reference to a non-existent class forces an exception during unserialize
        $brokenPayload = serialize(new FakeQueueJob());
        $brokenPayload = str_replace('FakeQueueJob', 'NonExistentJob', $brokenPayload);

        PendingJob::create([
            'queue' => 'default',
            'connection' => 'redis',
            'payload' => $brokenPayload,
            'delay_seconds' => 0,
        ]);

        $this->artisan('queue:flush-pending')->assertSuccessful();

        $pendingJob = PendingJob::first();
        $this->assertNotNull($pendingJob);
        $this->assertEquals(1, $pendingJob->attempts);
    }

    public function testDiscardsJobAfterMaxAttempts(): void {
        $brokenPayload = serialize(new FakeQueueJob());
        $brokenPayload = str_replace('FakeQueueJob', 'NonExistentJob', $brokenPayload);

        PendingJob::create([
            'queue' => 'default',
            'connection' => 'redis',
            'payload' => $brokenPayload,
            'delay_seconds' => 0,
            'attempts' => 9,
        ]);

        $this->artisan('queue:flush-pending')->assertSuccessful();

        $this->assertEquals(0, PendingJob::count());
    }

    public function testFlushesMultipleBatches(): void {
        Bus::fake();

        for ($i = 0; $i < 5; ++$i) {
            PendingJob::create([
                'queue' => 'default',
                'connection' => 'redis',
                'payload' => serialize(new FakeQueueJob()),
                'delay_seconds' => 0,
            ]);
        }

        $this->artisan('queue:flush-pending', ['--batch' => 2])
            ->assertSuccessful()
            ->expectsOutputToContain('Flushed 5 pending jobs');

        $this->assertEquals(0, PendingJob::count());
    }

    public function testNoOutputWhenNoPendingJobs(): void {
        $this->artisan('queue:flush-pending')
            ->assertSuccessful()
            ->doesntExpectOutputToContain('Flushed');
    }

    public function testProcessesJobsInIdOrder(): void {
        Bus::fake();

        $first = PendingJob::create([
            'queue' => 'first',
            'connection' => 'redis',
            'payload' => serialize(new FakeQueueJob()),
            'delay_seconds' => 0,
        ]);

        $second = PendingJob::create([
            'queue' => 'second',
            'connection' => 'redis',
            'payload' => serialize(new FakeQueueJob()),
            'delay_seconds' => 0,
        ]);

        $this->assertTrue($first->id < $second->id);

        $this->artisan('queue:flush-pending')->assertSuccessful();

        $this->assertEquals(0, PendingJob::count());
    }
}
