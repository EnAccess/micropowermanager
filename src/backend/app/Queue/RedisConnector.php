<?php

namespace App\Queue;

use Illuminate\Queue\Connectors\RedisConnector as BaseRedisConnector;

class RedisConnector extends BaseRedisConnector {
    /**
     * @param array<string, mixed> $config
     */
    public function connect(array $config): RedisQueue {
        return new RedisQueue(
            $this->redis,
            $config['queue'],
            $config['connection'] ?? $this->connection,
            $config['retry_after'] ?? 60,
            $config['block_for'] ?? null,
            (int) ($config['retry_connection_attempts'] ?? 3),
            (int) ($config['retry_connection_delay_ms'] ?? 1000),
        );
    }
}
