<?php

namespace Inensus\Ticket\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketingUserExternalRequest extends FormRequest {
    /**
     * Describes the rules which should be fulfilled by the request.
     *
     * @return array
     */
    public function rules(): array {
        return [
            'username' => 'required',
            'phone' => 'required',
        ];
    }

    public function getUserName(): string {
        return $this->input('username');
    }

    public function getPhone(): string {
        return (int) $this->input('phone');
    }
}
