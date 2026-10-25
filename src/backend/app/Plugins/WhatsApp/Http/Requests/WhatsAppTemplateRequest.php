<?php

namespace App\Plugins\WhatsApp\Http\Requests;

use App\Plugins\WhatsApp\Models\WhatsAppTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WhatsAppTemplateRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * The frontend speaks in human labels ("Payment receipt"); the database
     * stores the stable type key ("payment_receipt").
     */
    protected function prepareForValidation(): void {
        if ($this->has('type')) {
            $this->merge(['type' => WhatsAppTemplate::labelToType($this->string('type')->toString())]);
        }

        if ($this->has('status')) {
            $this->merge(['status' => strtolower($this->string('status')->toString())]);
        } else {
            $this->merge(['status' => WhatsAppTemplate::STATUS_DRAFT]);
        }

        if (!$this->filled('language')) {
            $this->merge(['language' => 'en']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(array_keys(WhatsAppTemplate::TYPES))],
            'language' => ['nullable', 'string', 'max:10'],
            'body' => ['required', 'string'],
            'status' => ['required', 'string', Rule::in([WhatsAppTemplate::STATUS_DRAFT, WhatsAppTemplate::STATUS_ACTIVE])],
        ];
    }
}
