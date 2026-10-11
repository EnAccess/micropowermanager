<?php

namespace App\Plugins\WhatsappMessaging\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WhatsappTemplateRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'template_name' => ['required', 'string', 'max:512'],
            'language_code' => ['required', 'string', 'max:10'],
            'parameters' => ['present', 'array', 'list'],
            'parameters.*' => ['required', 'string', 'distinct'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
