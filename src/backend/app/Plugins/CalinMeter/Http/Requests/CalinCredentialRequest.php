<?php

namespace App\Plugins\CalinMeter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalinCredentialRequest extends FormRequest {
    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'api_url' => ['required', 'url'],
            'user_id' => ['required', 'string'],
            'api_key' => ['required', 'string'],
        ];
    }
}
