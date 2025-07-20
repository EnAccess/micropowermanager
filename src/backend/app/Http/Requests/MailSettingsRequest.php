<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MailSettingsRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
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
            'mail_host' => 'required|min:5',
            'mail_port' => 'required|numeric',
            'mail_encryption' => 'required|min:3',
            'mail_username' => 'required|min:8|email',
            'mail_password' => 'required|min:3',
        ];
    }
}
