<?php

namespace App\Http\Requests;

use App\Models\Agent;
use Illuminate\Foundation\Http\FormRequest;

class CreateAgentReceiptRequest extends FormRequest {
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
            'agent_id' => ['required', 'exists:tenant.agents,id'],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $agent = Agent::query()->find($this->input('agent_id'));
                    if ($agent && (float) $value > $agent->balance) {
                        $fail('The receipt amount cannot exceed the amount the agent owes ('.$agent->balance.').');
                    }
                },
            ],
        ];
    }
}
