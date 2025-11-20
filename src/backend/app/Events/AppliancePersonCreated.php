<?php

namespace App\Events;

use App\Models\AppliancePerson;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppliancePersonCreated {
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public AppliancePerson $appliancePerson) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): PrivateChannel {
        return new PrivateChannel('appliancePerson.created');
    }
}
