<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketingUserExternalRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'username' => ['required'],
            'phone' => ['required'],
        ];
    }

    public function getUserName(): string {
        return $this->input('username');
    }

    public function getPhone(): string {
        return $this->input('phone');
    }
}
