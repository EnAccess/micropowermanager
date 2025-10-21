<?php

namespace Inensus\Ticket\Services;

use App\Models\Agent;
use App\Services\Interfaces\IAssociative;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
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
    ): Ticket {
        $ticket = $this->ticket->newQuery()->create(
            [
                'title' => $title,
                'content' => $content,
                'category_id' => $categoryId,
                'due_date' => $dueDate,
                'assigned_id' => $assignedId,
                'status' => 0,
            ]
        );

        $ticket->owner()->associate($owner);
        $ticket->save();

        return $ticket;
    }

    public function close(int $ticketId): Ticket {
        $ticket = $this->getById($ticketId);
        $ticketData = ['status' => 1];
        $this->update($ticket, $ticketData);

        return $ticket;
    }

    /**
     * @param LengthAwarePaginator<int, Ticket>|array<int, mixed> $tickets
     *
     * @return array<int, mixed>
     */
    public function getBatch(LengthAwarePaginator|array $tickets): array {
        $ticketData = [];
        foreach ($tickets as $index => $ticket) {
            $ticketData[$index] = $ticket;
            $ticketData[$index]['comments'] = $ticket->comments()->with('ticketUser')->get();
        }

        return $ticketData;
    }

    public function getById(int $ticketId): ?Ticket {
        return $this->ticket->newQuery()->with(['category', 'owner'])->where('id', $ticketId)->first();
    }

    /**
     * @param array<int|string, mixed> $ticketData
     */
    public function update(Ticket $ticket, array $ticketData): Ticket {
        $ticket->update($ticketData);
        $ticket->fresh();

        return $ticket;
    }

    /**
     * @return LengthAwarePaginator<int, Ticket>|Collection<int, Ticket>
     */
    public function getAll(
        ?int $limit = null,
        ?int $status = null,
        ?int $agentId = null,
        ?int $customerId = null,
        ?int $assignedId = null,
        ?int $categoryId = null,
    ): LengthAwarePaginator|Collection {
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

        $query->orderBy('created_at', 'desc');

        $tickets = $limit ? $query->paginate($limit) : $query->paginate();
        $ticketData = $this->getBatch($tickets);
        $tickets->setCollection(Collection::make($ticketData));

        return $tickets;
    }

    public function make(array $ticketData): Ticket {
        return $this->ticket->newQuery()->make($ticketData);
    }

    public function save(Model $ticket): bool {
        return $ticket->save();
    }

    /**
     * @param mixed $startDate
     * @param mixed $endDate
     *
     * @return Collection<int, Ticket>
     */
    public function getForOutsourceReport($startDate, $endDate): Collection {
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

    /**
     * @param mixed $startDate
     * @param mixed $endDate
     *
     * @return Collection<int, Ticket>
     */
    public function getForOutsourceReportForGeneration($startDate, $endDate) {
        return $this->ticket->newQuery()->with(['outsource', 'owner.person', 'category'])
            ->whereHas('category', static function ($q) {
                $q->where('out_source', 1);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
    }

    /**
     * @return LengthAwarePaginator<int, Ticket>
     */
    public function getForAgent(
        int $agentId,
        ?int $customerId = null,
    ): LengthAwarePaginator {
        $query = $this->ticket->newQuery()->with(['category', 'owner', 'assignedTo', 'comments.ticketUser']);

        if ($agentId !== 0) {
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
