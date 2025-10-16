<?php

namespace Inensus\Ticket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketingUserRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'user_id' => 'required|numeric',
        ];
    }

    public function getUserId(): int {
        return (int) $this->input('user_id');
    }
}
