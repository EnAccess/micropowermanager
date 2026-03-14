<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAddressRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @bodyParam village_id int required
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'village_id' => ['required'],
        ];
    }
}
