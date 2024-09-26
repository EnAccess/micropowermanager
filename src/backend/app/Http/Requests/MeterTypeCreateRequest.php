<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MeterTypeCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'max_current' => 'required|numeric|min:1',
            'phase' => 'required|numeric|min:1',
            'online' => 'required',
        ];
    }
}
