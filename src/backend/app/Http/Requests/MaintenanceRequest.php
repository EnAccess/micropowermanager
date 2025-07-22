<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MaintenanceRequest extends FormRequest {
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
            'title' => 'sometimes|string',
            'name' => 'required|min:3',
            'surname' => 'required|min:3',
            'birth_date' => 'sometimes|date_format:"Y-m-d"',
            'sex' => 'sometimes|in:male,female',
            'education' => 'sometimes|min:3',
            'city_id' => 'sometimes|exists:tenant.cities,id',
            'street' => 'sometimes|string|min:3',
            'email' => 'sometimes|email',
            'phone' => 'required|min:11|regex:(^\+)',
            'nationality' => 'sometimes|exists:tenant.countries,country_code',
        ];
    }

    public function getCityId(): int {
        return $this->input('city_id');
    }

    public function getPhone(): ?string {
        return $this->input('phone');
    }

    public function getStreet(): ?string {
        return $this->input('street');
    }

    public function getEmail(): ?string {
        return $this->input('email');
    }
}
