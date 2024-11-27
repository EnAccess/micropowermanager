<?php

namespace Inensus\Ticket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketingUserRequest extends FormRequest {
    /**
     * Describes the rules which should be fulfilled by the request.
     *
     * @return array
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
