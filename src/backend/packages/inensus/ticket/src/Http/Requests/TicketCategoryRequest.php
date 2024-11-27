<?php

namespace Inensus\Ticket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketCategoryRequest extends FormRequest {
    public function rules(): array {
        return [
            'labelName' => 'required',
            'labelColor' => 'sometimes|in:yellow,purple,blue,red,green,orange,black,sky,pink,lime,nocolor',
        ];
    }
}
