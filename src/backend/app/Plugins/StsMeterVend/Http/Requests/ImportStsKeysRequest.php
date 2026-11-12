<?php

namespace App\Plugins\StsMeterVend\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportStsKeysRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'csv' => ['required', 'file', 'mimes:csv,txt'],
        ];
    }
}
