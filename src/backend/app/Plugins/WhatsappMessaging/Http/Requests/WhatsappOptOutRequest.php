<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappMessaging\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WhatsappOptOutRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'consent_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
