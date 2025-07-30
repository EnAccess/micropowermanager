<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CreateUserRequest.
 */
class CreateAgentRequest extends FormRequest {
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
            'email' => 'required',
            'name' => 'required|min:3',
            'surname' => 'required|min:3',
            'password' => 'required|min:6',
            'city_id' => 'required',
            'agent_commission_id' => 'required',
        ];
    }
}
