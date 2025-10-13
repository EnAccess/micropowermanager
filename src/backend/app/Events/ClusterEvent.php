<?php

namespace App\Events;

use App\Models\Cluster;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ClusterEvent {
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param array<string, mixed> $data contains geo coordinates array or external url to fetch
     */
    public function __construct(
        public Cluster $cluster,
        public string $type,
        public array $data,
    ) {
        Log::debug('cluster event created');
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): PrivateChannel {
        return new PrivateChannel('clusters');
    }
}
