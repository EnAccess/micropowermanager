<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 06.09.18
 * Time: 15:05
 */

namespace Inensus\Ticket\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;

class LabelRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make that request
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tableNames = config('tickets.table_names');
        return [
            'labelName' => 'required:unique:' . $tableNames['ticket_categories'] . ', name',
            'labelColor' => 'sometimes|in:yellow,purple,blue,red,green,orange,black,sky,pink,lime,nocolor',
        ];
    }

}
