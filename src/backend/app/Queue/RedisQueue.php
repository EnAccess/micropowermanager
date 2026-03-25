<?php

namespace App\Queue;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Redis\Factory;
use Illuminate\Queue\RedisQueue as BaseRedisQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Predis\Connection\ConnectionException;

/**
 * Resilient Redis queue that retries on transient connection failures.
 *
 * Redis runs on Kubernetes and gets restarted occasionally (rolling deployments, OOM kills, node
 * evictions). Laravel's default RedisQueue treats any ConnectionException as fatal — a 1-second
 * blip kills the Horizon worker or loses a dispatched job. This subclass wraps every Redis
 * operation (push, pop, later) in a retry loop with exponential backoff so that brief outages
 * (1-3s) are absorbed transparently without any job loss or worker crashes.
 *
 * If all retries are exhausted the ConnectionException propagates up to the Dispatcher (see
 * App\Queue\Dispatcher), which catches it and buffers the job to MySQL as a last resort.
 *
 * Retry behaviour is configured via queue.php: `retry_connection_attempts` and
 * `retry_connection_delay_ms`.
 *
 * @see Dispatcher  DB fallback when retries are exhausted
 * @see RedisConnector  Wires this class in place of Laravel's RedisQueue
 */
class RedisQueue extends BaseRedisQueue {
    public function __construct(
        Factory $redis,
        $default = 'default',
        $connection = null,
        $retryAfter = 60,
        $blockFor = null,
        protected int $retryAttempts = 3,
        protected int $retryDelayMs = 1000,
    ) {
        parent::__construct($redis, $default, $connection, $retryAfter, $blockFor);
    }

    public function push($job, $data = '', $queue = null): mixed {
        return $this->retryOnConnectionFailure(fn () => parent::push($job, $data, $queue));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function pushRaw($payload, $queue = null, array $options = []): mixed {
        return $this->retryOnConnectionFailure(fn () => parent::pushRaw($payload, $queue, $options));
    }

    public function later($delay, $job, $data = '', $queue = null): mixed {
        return $this->retryOnConnectionFailure(fn () => parent::later($delay, $job, $data, $queue));
    }

    public function pop($queue = null, mixed $index = 0): ?Job {
        return $this->retryOnConnectionFailure(fn () => parent::pop($queue, $index));
    }

    /**
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     *
     * @throws ConnectionException
     */
    private function retryOnConnectionFailure(callable $callback): mixed {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->retryAttempts; ++$attempt) {
            try {
                return $callback();
            } catch (ConnectionException $e) {
                $lastException = $e;

                if ($attempt < $this->retryAttempts) {
                    $delayMs = $this->retryDelayMs * (2 ** ($attempt - 1));

                    Log::warning('Redis connection failed, retrying', [
                        'attempt' => $attempt,
                        'max_attempts' => $this->retryAttempts,
                        'retry_in_ms' => $delayMs,
                        'error' => $e->getMessage(),
                    ]);

                    Sleep::usleep($delayMs * 1000);
                }
            }
        }

        throw $lastException;
    }
}
