<?php

namespace App\Services;

use App\Models\Ticket\TicketCategory;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<TicketCategory>
 */
class TicketCategoryService implements IBaseService {
    /** @use HasCrudOperations<TicketCategory> */
    use HasCrudOperations;

    public function __construct(
        private TicketCategory $ticketCategory,
    ) {}

    protected function crudModel(): TicketCategory {
        return $this->ticketCategory;
    }

    public function getAll(?int $limit = null, ?bool $outsource = null): Collection|LengthAwarePaginator {
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
