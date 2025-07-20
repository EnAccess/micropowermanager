<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAddressRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @bodyParam city_id int required
     *
     * @return array<string, mixed>
     */
    public function rules() {
        return [
            'city_id' => 'required',
        ];
    }
}
