<?php

namespace Tests\Unit;

use App\Queue\RedisQueue;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Predis\Connection\ConnectionException;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Parameters;
use Tests\TestCase;

class RedisQueueRetryTest extends TestCase {
    private RedisFactory $redisFactory;

    private Connection $redisConnection;

    protected function setUp(): void {
        parent::setUp();

        $this->redisConnection = \Mockery::mock(Connection::class);
        $this->redisFactory = \Mockery::mock(RedisFactory::class);
        $this->redisFactory->shouldReceive('connection')->andReturn($this->redisConnection);

        Sleep::fake();
    }

    public function testPushSucceedsOnFirstAttempt(): void {
        $this->redisConnection->shouldReceive('eval')->once()->andReturn(1);

        $queue = new RedisQueue($this->redisFactory, 'default', 'default', 60, null);
        $queue->setContainer(app());

        $payload = json_encode(['uuid' => 'test-123', 'id' => 'test-123', 'displayName' => 'test', 'job' => 'test']);
        $queue->pushRaw($payload, 'default');

        // No exception thrown = success
        Sleep::assertNeverSlept();
    }

    public function testPushRetriesOnConnectionFailureThenSucceeds(): void {
        $exception = new ConnectionException(
            \Mockery::mock(NodeConnectionInterface::class),
            'Connection refused',
        );

        $this->redisConnection->shouldReceive('eval')
            ->once()
            ->andThrow($exception);

        $this->redisConnection->shouldReceive('eval')
            ->once()
            ->andReturn(1);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn ($msg): bool => str_contains($msg, 'Redis connection failed'));

        $queue = new RedisQueue($this->redisFactory, 'default', 'default', 60, null);
        $queue->setContainer(app());

        $queue->pushRaw('{"uuid":"test-456","id":"test-456","displayName":"test","job":"test"}', 'default');

        // No exception thrown = success after retry
        Sleep::assertSleptTimes(1);
    }

    public function testPushThrowsAfterAllRetriesExhausted(): void {
        $exception = new ConnectionException(
            \Mockery::mock(NodeConnectionInterface::class),
            'Connection refused',
        );

        $this->redisConnection->shouldReceive('eval')
            ->times(4)
            ->andThrow($exception);

        Log::shouldReceive('warning')->times(4);

        $queue = new RedisQueue($this->redisFactory, 'default', 'default', 60, null);
        $queue->setContainer(app());

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Connection refused');

        $queue->pushRaw('{"job":"test"}', 'default');
    }

    public function testRetryUsesExponentialBackoff(): void {
        $exception = new ConnectionException(
            \Mockery::mock(NodeConnectionInterface::class),
            'Connection refused',
        );

        $this->redisConnection->shouldReceive('eval')
            ->times(4)
            ->andThrow($exception);

        Log::shouldReceive('warning')->times(4);

        $queue = new RedisQueue($this->redisFactory, 'default', 'default', 60, null);
        $queue->setContainer(app());

        try {
            $queue->pushRaw('{"job":"test"}', 'default');
        } catch (ConnectionException) {
            // expected
        }

        // Backoff schedule: 1s, 5s, 10s, 60s (plus up to 500ms jitter each)
        Sleep::assertSleptTimes(4);
    }

    public function testPopRetriesOnConnectionFailure(): void {
        $nodeConnection = \Mockery::mock(NodeConnectionInterface::class);
        $nodeConnection->shouldReceive('getParameters')->andReturn(
            new Parameters(['host' => 'localhost']),
        );

        $exception = new ConnectionException($nodeConnection, 'Connection refused');

        $firstCall = true;
        $this->redisConnection->shouldReceive('eval')
            ->andReturnUsing(function () use (&$firstCall, $exception): null {
                if ($firstCall) {
                    $firstCall = false;

                    throw $exception;
                }

                return null;
            });

        Log::shouldReceive('warning')->once();

        $queue = new RedisQueue($this->redisFactory, 'default', 'default', 60, null);
        $queue->setContainer(app());

        $result = $queue->pop('default');

        $this->assertNull($result);
        Sleep::assertSleptTimes(1);
    }
}
