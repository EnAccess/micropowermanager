<?php

namespace Inensus\Ticket\Services;

use App\Services\Interfaces\IBaseService;
use Inensus\Ticket\Models\TicketCategory;

class TicketCategoryService implements IBaseService
{
    public function __construct(private TicketCategory $ticketCategory)
    {
    }

    public function getById($categoryId)
    {
        return $this->ticketCategory->newQuery()->find($categoryId);
    }

    public function create($ticketCategoryData)
    {
        return $this->ticketCategory->newQuery()->create($ticketCategoryData);
    }

    public function update($model, array $data): Model
    {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool
    {
        throw new \Exception('Method delete() not yet implemented.');
    }

    public function getAll($limit = null, $outsource = null)
    {
        $ticketCategories = $this->ticketCategory->newQuery();

        if ($outsource) {
            $ticketCategories->where('out_source', 1)->get();
        }

        if ($limit) {
            return $ticketCategories->paginate($limit);
        }

        return $ticketCategories->get();
    }
}
