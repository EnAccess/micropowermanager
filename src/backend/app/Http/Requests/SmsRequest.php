<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SmsRequest extends FormRequest {
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
            // A WhatsApp template message is built from the template and its values.
            'message' => ['required_without:template_id'],
            'person_id' => ['required_without_all:phone'],
            'template_id' => ['nullable', 'string'],
            'template_values' => ['nullable', 'array'],
        ];
    }
}
