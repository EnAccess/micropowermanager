<?php

namespace Inensus\Ticket\Services;

use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Inensus\Ticket\Models\TicketCategory;

/**
 * @implements IBaseService<TicketCategory>
 */
class TicketCategoryService implements IBaseService {
    public function __construct(
        private TicketCategory $ticketCategory,
    ) {}

    public function getById(int $categoryId): TicketCategory {
        return $this->ticketCategory->newQuery()->find($categoryId);
    }

    public function create(array $ticketCategoryData): TicketCategory {
        return $this->ticketCategory->newQuery()->create($ticketCategoryData);
    }

    public function update($model, array $data): TicketCategory {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
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
