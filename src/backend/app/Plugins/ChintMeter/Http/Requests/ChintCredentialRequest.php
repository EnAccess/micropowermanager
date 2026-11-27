<?php

namespace App\Plugins\ChintMeter\Http\Requests;

use App\Plugins\ChintMeter\Enums\TariffAppliedBy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChintCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'api_url' => ['sometimes', 'required', 'url'],
            'user_name' => ['required'],
            'user_password' => ['required'],
            'tariff_applied_by' => ['sometimes', 'required', Rule::enum(TariffAppliedBy::class)],
        ];
    }
}
