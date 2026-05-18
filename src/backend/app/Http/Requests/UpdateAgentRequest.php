<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgentRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'name' => ['sometimes', 'string', 'min:3'],
            'surname' => ['sometimes', 'string', 'min:3'],
            'gender' => ['sometimes', 'string'],
            'birthday' => ['sometimes', 'date'],
            'phone' => ['sometimes', 'string'],
            'commissionTypeId' => ['sometimes', 'integer'],
        ];
    }
}
