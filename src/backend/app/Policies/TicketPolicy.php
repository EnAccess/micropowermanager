<?php

namespace App\Policies;

use App\Models\User;
use Inensus\Ticket\Models\Ticket;

class TicketPolicy {
    public function view(User $user, Ticket $ticket): bool {
        return $user->can('tickets.view');
    }

    public function create(User $user): bool {
        return $user->can('tickets.create');
    }

    public function update(User $user, Ticket $ticket): bool {
        return $user->can('tickets.update');
    }

    public function delete(User $user, Ticket $ticket): bool {
        return $user->can('tickets.delete');
    }

    public function export(User $user): bool {
        return $user->can('tickets.export');
    }
}
