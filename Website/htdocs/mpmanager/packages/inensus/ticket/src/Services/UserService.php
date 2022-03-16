<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 06.09.18
 * Time: 11:14
 */

namespace Inensus\Ticket\Services;


use Illuminate\Support\Facades\Log;
use Inensus\Ticket\Exceptions\ApiUserNotFound;
use Inensus\Ticket\Models\UserModel;
use Inensus\Ticket\Trello\Users;

class UserService
{

    /**
     * The gateway to Trello User Api
     *
     * @var Users
     */
    private $users;
    private $userModel;

    public function __construct(Users $users, UserModel $user)
    {
        $this->userModel = $user;
        $this->users = $users;
    }

    /**
     * Finds the user on Trello
     *
     * @param string $userTag the username @ Trello
     */
    public function getUser($userTag)
    {
        try {
            $user = $this->users->find($userTag);
            return $user;

        } catch (ApiUserNotFound $e) {
            Log::critical($userTag . ' not found in Ticketing system');
        }
        return null;

    }

    public function getAllUsers()
    {
        return $this->userModel->get();
    }

    public function getByExternId($externId)
    {
        return $this->userModel->where('extern_id', $externId)->first();
    }
}
