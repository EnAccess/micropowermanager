<?php

declare(strict_types=1);

namespace MPM\User\Events;

use App\Models\User;

class UserCreatedEvent {
    public function __construct(public User $user, public bool $shouldSyncUser) {}
}
