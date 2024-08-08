<?php

namespace Inensus\Ticket\Services;

use App\Services\Interfaces\IBaseService;
use Inensus\Ticket\Models\TicketOutsource;

class TicketOutSourceService implements IBaseService
{
    public function __construct(private TicketOutsource $ticketOutsource)
    {
    }

    public function getById($ticketOutsourceId)
    {
        $this->ticketOutsource->newQuery()->find($ticketOutsourceId);
    }

    public function create($ticketOutsourceData)
    {
        return $this->ticketOutsource->newQuery()->create($ticketOutsourceData);
    }

    public function update($model, array $data): Model
    {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool
    {
        throw new \Exception('Method delete() not yet implemented.');
    }

    public function getAll(?int $limit = null): Collection
    {
        throw new \Exception('Method getAll() not yet implemented.');
    }
}
