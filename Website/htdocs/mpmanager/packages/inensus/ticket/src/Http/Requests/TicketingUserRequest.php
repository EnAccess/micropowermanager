<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 05.09.18
 * Time: 15:01
 */

namespace Inensus\Ticket\Http\Requests;


use App\Services\SessionService;
use Illuminate\Foundation\Http\FormRequest;

class TicketingUserRequest extends FormRequest
{

    /**
     * Describes the rules which should be fulfilled by the request
     * @return array
     */
    public function rules(): array
    {
        $sessionService = app()->make(SessionService::class);
        $database=$sessionService->getAuthenticatedUserDatabaseName();

        return [
            'username' => 'required|unique:'.$database.'.ticket_users,user_name',
            'usertag' => 'required|unique:'.$database.'.ticket_users,user_name',
        ];
    }
}