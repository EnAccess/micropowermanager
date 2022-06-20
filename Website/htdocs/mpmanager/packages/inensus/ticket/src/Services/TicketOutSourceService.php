<?php

namespace Inensus\Ticket\Services;

use App\Services\BaseService;
use App\Services\IBaseService;
use Inensus\Ticket\Models\TicketOutsource;

class TicketOutSourceService  implements IBaseService
{

    public function __construct(private TicketOutsource $ticketOutsource)
    {
        parent::__construct([$ticketOutsource]);
    }

    public function getById($ticketOutsourceId)
    {
        $this->ticketOutsource->newQuery()->find($ticketOutsourceId);
    }

    public function create($ticketOutsourceData)
    {
       return $this->ticketOutsource->newQuery()->create($ticketOutsourceData);
    }

    public function update($model, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }

    public function getAll($limit = null)
    {
        // TODO: Implement getAll() method.
    }
}
