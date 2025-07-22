<?php

namespace Inensus\Ticket\Services;

use App\Models\Agent;
use App\Services\Interfaces\IAssociative;
use Illuminate\Database\Eloquent\Collection;
use Inensus\Ticket\Models\Ticket;

/**
 * @implements IAssociative<Ticket>
 */
class TicketService implements IAssociative {
    public function __construct(
        private Ticket $ticket,
    ) {}

    public function create(
        string $title,
        string $content,
        int $categoryId,
        int $assignedId,
        ?string $dueDate,
        mixed $owner,
    ) {
        $ticket = $this->ticket->newQuery()->create(
            [
                'title' => $title,
                'content' => $content,
                'category_id' => $categoryId,
                'due_date' => $dueDate,
                'assigned_id' => $assignedId,
            ]
        );

        $ticket->owner()->associate($owner);
        $ticket->save();

        return $ticket;
    }

    public function close($ticketId): Ticket {
        $ticket = $this->getById($ticketId);
        $ticketData = ['status' => 1];
        $this->update($ticket, $ticketData);

        return $ticket;
    }

    public function getBatch($tickets) {
        foreach ($tickets as $index => $ticket) {
            $tickets[$index]['comments'] = $ticket->comments()->with('ticketUser')->get();
        }

        return $tickets;
    }

    public function getById($ticketId) {
        return $this->ticket->newQuery()->with(['category', 'owner'])->where('id', $ticketId)->first();
    }

    public function update($ticket, $ticketData) {
        $ticket->update($ticketData);
        $ticket->fresh();

        return $ticket;
    }

    public function getAll(
        $limit = null,
        $status = null,
        $agentId = null,
        $customerId = null,
        $assignedId = null,
        $categoryId = null,
    ) {
        $query = $this->ticket->newQuery()->with(['category', 'owner', 'assignedTo', 'comments.ticketUser']);

        if ($agentId) {
            $query->whereHasMorph(
                'creator',
                [Agent::class],
                static function ($q) use ($agentId) {
                    $q->where('id', $agentId);
                }
            );
        }

        if ($status != null) {
            $query->where('status', $status);
        }

        if ($customerId) {
            $query->where('owner_id', $customerId);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($assignedId) {
            $query->where('assigned_id', $assignedId);
        }

        if ($limit) {
            $tickets = $query->paginate($limit);
        } else {
            $tickets = $query->paginate();
        }

        $ticketData = $this->getBatch($tickets);
        $tickets->setCollection(Collection::make($ticketData));

        return $tickets;
    }

    public function make(array $ticketData): Ticket {
        return $this->ticket->newQuery()->make($ticketData);
    }

    public function save($ticket): bool {
        return $ticket->save();
    }

    public function getForOutsourceReport($startDate, $endDate) {
        return $this->ticket->newQuery()->with(['outsource', 'assignedTo', 'category'])
            ->whereHas('category', static function ($q) {
                $q->where('out_source', 1);
            })
            ->whereHas('assignedTo', static function ($q) {
                $q->whereNotNull('owner_id');
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
    }

    public function getForOutsourceReportForGeneration($startDate, $endDate) {
        return $this->ticket->newQuery()->with(['outsource', 'owner.person', 'category'])
            ->whereHas('category', static function ($q) {
                $q->where('out_source', 1);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
    }

    public function getForAgent($agentId, $customerId = null) {
        $query = $this->ticket->newQuery()->with(['category', 'owner', 'assignedTo', 'comments.ticketUser']);

        if ($agentId) {
            $query->whereHasMorph(
                'creator',
                [Agent::class],
                static function ($q) use ($agentId) {
                    $q->where('id', $agentId);
                }
            );
        }

        if ($customerId) {
            $query->where('owner_id', $customerId);
        }

        return $query->paginate();
    }
}
