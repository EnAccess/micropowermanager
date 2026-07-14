<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PersonListRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array {
        return [
            // When 1 (the default), registered customers are returned; when 0, non-customers like the contact person of a meter manufacturer.
            'is_customer' => ['sometimes', 'integer'],
            // Only return customers of this agent.
            'agent_id' => ['sometimes', 'integer'],
            // When true, only customers with a payment in the last 25 days are returned; when false, only customers without one.
            'active_customer' => ['sometimes', 'boolean'],
            // Filter by primary address city/village id.
            'city_id' => ['sometimes', 'integer'],
            // Minimum total paid amount for the customer.
            'total_paid_min' => ['sometimes', 'numeric'],
            // Maximum total paid amount for the customer.
            'total_paid_max' => ['sometimes', 'numeric'],
            // ISO date string for minimum latest payment date.
            'latest_payment_from' => ['sometimes', 'date'],
            // ISO date string for maximum latest payment date.
            'latest_payment_to' => ['sometimes', 'date'],
            // ISO date string for minimum registration date.
            'registration_from' => ['sometimes', 'date'],
            // ISO date string for maximum registration date.
            'registration_to' => ['sometimes', 'date'],
            // Filter by device/appliance type.
            'device_type' => ['sometimes', 'string'],
            // The number of items per page.
            'per_page' => ['sometimes', 'integer'],
        ];
    }

    /**
     * @return array{is_customer: int, agent_id: int|null, active_customer: bool|null, city_id: int|null, total_paid_min: float|null, total_paid_max: float|null, latest_payment_from: string|null, latest_payment_to: string|null, registration_from: string|null, registration_to: string|null, device_type: string|null}
     */
    public function filters(): array {
        return [
            'is_customer' => $this->integer('is_customer', 1),
            'agent_id' => $this->integer('agent_id') ?: null,
            'active_customer' => $this->has('active_customer') ? $this->boolean('active_customer') : null,
            'city_id' => $this->integer('city_id') ?: null,
            'total_paid_min' => $this->filled('total_paid_min') ? $this->float('total_paid_min') : null,
            'total_paid_max' => $this->filled('total_paid_max') ? $this->float('total_paid_max') : null,
            'latest_payment_from' => $this->string('latest_payment_from')->toString() ?: null,
            'latest_payment_to' => $this->string('latest_payment_to')->toString() ?: null,
            'registration_from' => $this->string('registration_from')->toString() ?: null,
            'registration_to' => $this->string('registration_to')->toString() ?: null,
            'device_type' => $this->string('device_type')->toString() ?: null,
        ];
    }
}
