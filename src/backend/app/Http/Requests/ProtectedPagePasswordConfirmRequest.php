<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProtectedPagePasswordConfirmRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6'],
            'password_confirmation' => ['required', 'string', 'same:password'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array {
        return [
            'token.required' => 'Reset token is required.',
            'password.required' => 'New password is required.',
            'password.min' => 'Password must be at least 6 characters long.',
            'password_confirmation.required' => 'Password confirmation is required.',
            'password_confirmation.same' => 'Password confirmation does not match.',
        ];
    }
}
