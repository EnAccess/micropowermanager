<?php

namespace App\Plugins\SteamaMeter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SteamaCustomerRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'id' => ['required', Rule::unique('tenant.steama_customers')->ignore($this->id)],
            'low_balance_warning' => ['required'],
            'energy_price' => ['required'],
        ];
    }
}
