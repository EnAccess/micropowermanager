<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyRegistrationRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'name' => 'required',
            'address' => 'required',
            'phone' => 'required',
            'email' => 'required|unique:database_proxies',
            'protected_page_password' => 'required',
        ];
    }
}
