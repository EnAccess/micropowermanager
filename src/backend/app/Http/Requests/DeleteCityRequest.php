<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteCityRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            // The id of the village every address linked to the deleted village is moved to.
            'reassign_addresses_to' => ['sometimes', 'integer', 'exists:tenant.cities,id'],
        ];
    }
}
