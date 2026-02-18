<?php

namespace App\Plugins\SparkShs\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SparkShsCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
        ];
    }
}
