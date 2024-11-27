<?php

namespace Inensus\AngazaSHS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AngazaCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'client_id' => ['required'],
            'client_secret' => ['required'],
        ];
    }
}
