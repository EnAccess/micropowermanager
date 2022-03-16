<?php

namespace App\Events;

use App\Models\History;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class HistoryEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @var History
     */
    public $historyModel;

    /**
     * Create a new event instance.
     *
     * @param History $historyModel
     */
    public function __construct(History $historyModel)
    {
        $this->broadcastQueue = config('services.queues.energy');

        //initialize the history model
        $this->historyModel = $historyModel;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return PrivateChannel
     */
    public function broadcastOn()
    {
        return new PrivateChannel('histories');
    }
}
