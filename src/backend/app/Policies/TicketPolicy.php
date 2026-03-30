<?php

namespace App\Policies;

use App\Models\Ticket\Ticket;
use App\Models\User;

class TicketPolicy {
    public function view(User $user, Ticket $ticket): bool {
        return $user->can('tickets');
    }

    public function create(User $user): bool {
        return $user->can('tickets');
    }

    public function update(User $user, Ticket $ticket): bool {
        return $user->can('tickets');
    }

    public function delete(User $user, Ticket $ticket): bool {
        return $user->can('tickets');
    }

    public function export(User $user): bool {
        return $user->can('tickets');
    }
}
