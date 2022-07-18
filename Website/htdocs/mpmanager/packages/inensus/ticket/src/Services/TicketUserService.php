<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 06.09.18
 * Time: 11:14
 */

namespace Inensus\Ticket\Services;

use App\Services\IBaseService;
use Illuminate\Support\Facades\Log;
use Inensus\Ticket\Exceptions\ApiUserNotFound;
use Inensus\Ticket\Models\TicketUser;
use Inensus\Ticket\Trello\Users;

class TicketUserService  implements IBaseService
{
    public function __construct(
        private TicketUser $ticketUser,
        private Users $usersGateway
    ) {

    }

    /**
     * Finds the user on Trello
     *
     * @param string $userTag the username @ Trello
     */
    public function getByTag($userTag)
    {
        try {

            return $this->usersGateway->find($userTag);
        } catch (ApiUserNotFound $e) {
            Log::critical($userTag . ' not found in Ticketing system');
        }
        return null;
    }

    public function getAll($limit = null, $outsource = null)
    {
        $ticketUsers = $this->ticketUser->newQuery();

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

}
