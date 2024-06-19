<?php

namespace App\Events;

use App\Models\AssetPerson;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssetPersonCreated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @var AssetPerson
     */
    private $assetPerson;

    public function __construct(AssetPerson $assetPerson)
    {
        $this->assetPerson = $assetPerson;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return PrivateChannel
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('assetPerson.created');
    }
}
