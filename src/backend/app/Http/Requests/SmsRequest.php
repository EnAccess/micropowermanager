<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SmsRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array {
        return [
            'message' => 'required',
            'person_id' => 'required_without_all:phone',
        ];
    }
}
