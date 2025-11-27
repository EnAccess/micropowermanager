<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketCategoryRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'labelName' => ['required'],
            'labelColor' => ['sometimes', 'in:yellow,purple,blue,red,green,orange,black,sky,pink,lime,nocolor'],
        ];
    }
}
