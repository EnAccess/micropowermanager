<?php

namespace App\Plugins\AngazaSHS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AngazaCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'client_id' => ['required'],
            'client_secret' => ['required'],
        ];
    }
}
