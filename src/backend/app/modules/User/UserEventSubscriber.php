<?php

declare(strict_types=1);

namespace MPM\User;

use Illuminate\Events\Dispatcher;
use MPM\User\Events\UserCreatedEvent;

class UserEventSubscriber {
    public function subscribe(Dispatcher $events) {
        $events->listen([UserCreatedEvent::class], UserListener::class);
    }
}
