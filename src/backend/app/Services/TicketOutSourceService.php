<?php

namespace App\Services;

use App\Models\Ticket\TicketOutsource;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;

/**
 * @implements IBaseService<TicketOutsource>
 */
class TicketOutSourceService implements IBaseService {
    public function __construct(
        private TicketOutsource $ticketOutsource,
    ) {}

    public function getById(int $ticketOutsourceId): TicketOutsource {
        return $this->ticketOutsource->newQuery()->find($ticketOutsourceId);
    }

    public function create(array $ticketOutsourceData): TicketOutsource {
        return $this->ticketOutsource->newQuery()->create($ticketOutsourceData);
    }

    public function update($model, array $data): TicketOutsource {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @return Collection<int, TicketOutsource>
     */
    public function getAll(?int $limit = null): Collection {
        throw new \Exception('Method getAll() not yet implemented.');
    }
}
