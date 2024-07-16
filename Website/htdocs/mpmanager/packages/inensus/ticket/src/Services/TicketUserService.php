<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 06.09.18
 * Time: 11:14.
 */

namespace Inensus\Ticket\Services;

use App\Models\User;
use App\Services\IBaseService;
use Inensus\Ticket\Models\TicketUser;

class TicketUserService implements IBaseService
{
    public function __construct(
        private TicketUser $ticketUser,
        private User $user,
    ) {
    }

    public function getAll($limit = null, $outsource = null)
    {
        $ticketUsers = $this->user::with('relationTicketUser');

        if ($outsource) {
            $ticketUsers->where('out_source', 1);
        }

        if ($limit) {
            return $ticketUsers->paginate($limit);
        }

        return $ticketUsers->get();
    }

    public function getById($externId)
    {
        return $this->ticketUser->newQuery()->where('extern_id', $externId)->first();
    }

    public function create($ticketUserData)
    {
        return $this->ticketUser->newQuery()->create($ticketUserData);
    }

    public function update($model, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }

    public function findByPhone(string $phone): TicketUser
    {
        /** @var TicketUser $result */
        $result = $this->ticketUser->newQuery()->where('phone', '=', $phone)
            ->firstOrFail();

        return $result;
    }

    public function findOrCreateByUser(User $user): TicketUser
    {
        try {
            /** @var TicketUser $result */
            $result = $this->ticketUser->newQuery()->where('user_id', '=', $user->getId())
                ->firstOrFail();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            $result = $this->ticketUser->newQuery()->create([
                'user_id' => $user->getId(),
                'phone' => null,
                'user_name' => $user->getName(),
            ]);
        }

        return $result;
    }
}
