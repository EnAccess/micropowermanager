<?php

namespace App\Events;

use App\Models\Person\Person;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PersonDeleting {
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    private $person;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Person $person) {
        $this->person = $person;
    }

    /**
     * Get the Person model instance associated with the event.
     *
     * @return Person
     */
    public function getPerson(): Person {
        return $this->person;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return PrivateChannel
     */
    public function broadcastOn(): PrivateChannel {
        return new PrivateChannel('person.deleted');
    }
}
