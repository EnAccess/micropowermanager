<?php

namespace App\Http\Requests;

use App\DTO\PersonListFilters;
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

    public function filters(): PersonListFilters {
        return new PersonListFilters(
            isCustomer: $this->integer('is_customer', 1),
            agentId: $this->integer('agent_id') ?: null,
            activeCustomer: $this->has('active_customer') ? $this->boolean('active_customer') : null,
            cityId: $this->integer('city_id') ?: null,
            totalPaidMin: $this->filled('total_paid_min') ? $this->float('total_paid_min') : null,
            totalPaidMax: $this->filled('total_paid_max') ? $this->float('total_paid_max') : null,
            latestPaymentFrom: $this->string('latest_payment_from')->toString() ?: null,
            latestPaymentTo: $this->string('latest_payment_to')->toString() ?: null,
            registrationFrom: $this->string('registration_from')->toString() ?: null,
            registrationTo: $this->string('registration_to')->toString() ?: null,
            deviceType: $this->string('device_type')->toString() ?: null,
        );
    }
}
