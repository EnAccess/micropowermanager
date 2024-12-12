<?php

namespace Inensus\Ticket\Services;

use App\Models\User;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Inensus\Ticket\Models\TicketUser;

/**
 * @implements IBaseService<TicketUser>
 */
class TicketUserService implements IBaseService {
    public function __construct(
        private TicketUser $ticketUser,
        private User $user,
    ) {}

    public function getAll(?int $limit = null, ?bool $outsource = null): Collection|LengthAwarePaginator {
        $ticketUsers = $this->user::with('relationTicketUser');

        if ($outsource) {
            $ticketUsers->where('out_source', 1);
        }

        if ($limit) {
            return $ticketUsers->paginate($limit);
        }

        return $ticketUsers->get();
    }

    public function getById(int $externId): TicketUser {
        return $this->ticketUser->newQuery()->where('extern_id', $externId)->first();
    }

    public function create($ticketUserData): TicketUser {
        return $this->ticketUser->newQuery()->create($ticketUserData);
    }

    public function update($model, array $data): TicketUser {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    public function findByPhone(string $phone): TicketUser {
        /** @var TicketUser $result */
        $result = $this->ticketUser->newQuery()->where('phone', '=', $phone)
            ->firstOrFail();

        return $result;
    }

    public function findOrCreateByUser(User $user): TicketUser {
        try {
            /** @var TicketUser $result */
            $result = $this->ticketUser->newQuery()->where('user_id', '=', $user->getId())
                ->firstOrFail();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            $result = $this->ticketUser->newQuery()->create([
                'user_name' => $user->getName(),
                'phone' => null,
                'out_source' => 0,
                'user_id' => $user->getId(),
            ]);
        }

        return $result;
    }
}
