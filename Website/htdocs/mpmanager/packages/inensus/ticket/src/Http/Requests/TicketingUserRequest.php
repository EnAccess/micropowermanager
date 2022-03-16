<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 05.09.18
 * Time: 15:01
 */

namespace Inensus\Ticket\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;

class TicketingUserRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Describes the rules which should be fulfilled by the request
     * @return array
     */
    public function rules(): array
    {

        //get table names
        $tableNames = config('tickets.table_names');

        return [
            'username' => 'required|unique:' . $tableNames['user'] . ',user_name',
            'usertag' => 'required|unique:' . $tableNames['user'] . ',user_name',
        ];
    }
}