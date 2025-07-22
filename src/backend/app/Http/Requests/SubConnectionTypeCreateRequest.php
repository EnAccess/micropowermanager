<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubConnectionTypeCreateRequest extends FormRequest {
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
            'name' => 'required|min:3',
            'connection_type_id' => 'required|numeric',
            'tariff_id' => 'required|numeric',
        ];
    }
}
