<?php

namespace Inensus\Ticket\Services;


use App\Services\IBaseService;
use Inensus\Ticket\Models\TicketCategory;

class TicketCategoryService  implements IBaseService
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

    public function update($model, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }

    public function getAll($limit = null,$outsource = null)
    {
        $ticketCategories = $this->ticketCategory->newQuery();

        if ($outsource){
            $ticketCategories->where('out_source', 1)->get();
        }

        if ($limit) {
           return $ticketCategories->paginate($limit);
        }

        return $ticketCategories->get();
    }
}
