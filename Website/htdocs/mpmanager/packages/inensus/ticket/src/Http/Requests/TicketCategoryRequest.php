<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 06.09.18
 * Time: 15:05
 */

namespace Inensus\Ticket\Http\Requests;


use App\Services\SessionService;
use Illuminate\Foundation\Http\FormRequest;

class TicketCategoryRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'labelName' => 'required',
            'labelColor' => 'sometimes|in:yellow,purple,blue,red,green,orange,black,sky,pink,lime,nocolor',
        ];
    }

}
