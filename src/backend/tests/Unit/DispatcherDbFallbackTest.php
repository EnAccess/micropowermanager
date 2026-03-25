<?php

namespace Tests\Unit;

use App\Models\PendingJob;
use App\Queue\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\QueueingDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Predis\Connection\ConnectionException;
use Predis\Connection\NodeConnectionInterface;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class DispatcherDbFallbackTest extends TestCase {
    use RefreshMultipleDatabases;

    private QueueingDispatcher $innerDispatcher;

    private Dispatcher $dispatcher;

    protected function setUp(): void {
        parent::setUp();

        PendingJob::query()->delete();

        $this->innerDispatcher = \Mockery::mock(QueueingDispatcher::class);
        $this->dispatcher = new Dispatcher($this->innerDispatcher);
    }

    public function testDispatchDelegatesToInnerDispatcherWhenRedisIsAvailable(): void {
        $job = new FakeQueueJob();

        $this->innerDispatcher->shouldReceive('dispatch')
            ->once()
            ->with($job)
            ->andReturn('dispatched');

        $result = $this->dispatcher->dispatch($job);

        $this->assertEquals('dispatched', $result);
        $this->assertEquals(0, PendingJob::count());
    }

    public function testDispatchBuffersJobToDatabaseOnConnectionException(): void {
        $job = new FakeQueueJob('txn-500', 10000);
        $job->onQueue('payments')
            ->onConnection('redis');

        $exception = new ConnectionException(
            \Mockery::mock(NodeConnectionInterface::class),
            'Connection refused',
        );

        $this->innerDispatcher->shouldReceive('dispatch')
            ->once()
            ->andThrow($exception);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn ($msg): bool => str_contains($msg, 'Redis unavailable'));

        $result = $this->dispatcher->dispatch($job);

        $this->assertNull($result);
        $this->assertEquals(1, PendingJob::count());

        $pendingJob = PendingJob::first();
        $this->assertEquals('payments', $pendingJob->queue);
        $this->assertEquals('redis', $pendingJob->connection);

        $unserialized = unserialize($pendingJob->payload);
        $this->assertInstanceOf(FakeQueueJob::class, $unserialized);
        $this->assertEquals('txn-500', $unserialized->transactionId);
        $this->assertEquals(10000, $unserialized->amount);
    }

    public function testDispatchToQueueBuffersOnConnectionException(): void {
        $job = new FakeQueueJob();

        $exception = new ConnectionException(
            \Mockery::mock(NodeConnectionInterface::class),
            'Connection refused',
        );

        $this->innerDispatcher->shouldReceive('dispatchToQueue')
            ->once()
            ->andThrow($exception);

        Log::shouldReceive('warning')->once();

        $result = $this->dispatcher->dispatchToQueue($job);

        $this->assertNull($result);
        $this->assertEquals(1, PendingJob::count());
    }

    public function testDispatchSyncDoesNotCatchConnectionException(): void {
        $job = new FakeQueueJob();

        $exception = new ConnectionException(
            \Mockery::mock(NodeConnectionInterface::class),
            'Connection refused',
        );

        $this->innerDispatcher->shouldReceive('dispatchSync')
            ->once()
            ->andThrow($exception);

        $this->expectException(ConnectionException::class);

        $this->dispatcher->dispatchSync($job);
    }

    public function testBufferedJobUsesDefaultQueueWhenNotSet(): void {
        $job = new FakeQueueJob();

        $exception = new ConnectionException(
            \Mockery::mock(NodeConnectionInterface::class),
            'Connection refused',
        );

        $this->innerDispatcher->shouldReceive('dispatch')
            ->once()
            ->andThrow($exception);

        Log::shouldReceive('warning')->once();

        $this->dispatcher->dispatch($job);

        $pendingJob = PendingJob::first();
        $this->assertEquals('default', $pendingJob->queue);
        $this->assertEquals('redis', $pendingJob->connection);
        $this->assertEquals(0, $pendingJob->delay_seconds);
    }

    public function testMultipleFailedDispatchesCreateMultiplePendingJobs(): void {
        $exception = new ConnectionException(
            \Mockery::mock(NodeConnectionInterface::class),
            'Connection refused',
        );

        $this->innerDispatcher->shouldReceive('dispatch')
            ->times(3)
            ->andThrow($exception);

        Log::shouldReceive('warning')->times(3);

        for ($i = 0; $i < 3; ++$i) {
            $job = new FakeQueueJob("txn-{$i}", 1000 * $i);
            $job->onQueue("queue-{$i}");
            $this->dispatcher->dispatch($job);
        }

        $this->assertEquals(3, PendingJob::count());
    }

    public function testDispatchAfterResponseBuffersOnConnectionException(): void {
        $job = new FakeQueueJob();

        $exception = new ConnectionException(
            \Mockery::mock(NodeConnectionInterface::class),
            'Connection refused',
        );

        $this->innerDispatcher->shouldReceive('dispatchAfterResponse')
            ->once()
            ->andThrow($exception);

        Log::shouldReceive('warning')->once();

        $this->dispatcher->dispatchAfterResponse($job);

        $this->assertEquals(1, PendingJob::count());
    }
}

class FakeQueueJob implements ShouldQueue {
    use Queueable;
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;

    public function __construct(
        public readonly string $transactionId = 'txn-001',
        public readonly int $amount = 5000,
    ) {
        $this->onConnection('redis');
    }

    public function handle(): void {
        // Process the fake transaction
    }
}
