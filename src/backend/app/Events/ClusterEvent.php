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
     * @var Cluster
     */
    public $cluster;
    /**
     * @var string
     */
    public $type;
    /**
     * @var mixed contains geo coordinates array or external url to fetch
     */
    public $data;

    /**
     * Create a new event instance.
     *
     * @param Cluster $cluster
     * @param string  $type
     * @param         $data
     */
    public function __construct(Cluster $cluster, string $type, $data) {
        Log::debug('cluster event created');
        $this->cluster = $cluster;
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return PrivateChannel
     */
    public function broadcastOn(): PrivateChannel {
        return new PrivateChannel('clusters');
    }
}
