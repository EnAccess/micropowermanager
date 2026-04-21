<?php

declare(strict_types=1);

namespace App\Plugins\SmsTransactionParser\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SmsParsingRuleRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        $ruleId = $this->route('id');

        return [
            'provider_name' => 'required|string|max:255|unique:tenant.sms_parsing_rules,provider_name'.($ruleId ? ','.$ruleId : ''),
            'template' => ['required', 'string'],
            'sender_pattern' => ['nullable', 'string'],
            'enabled' => ['boolean'],
        ];
    }
}
