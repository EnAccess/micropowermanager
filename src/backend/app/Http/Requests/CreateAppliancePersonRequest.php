<?php

namespace App\Http\Requests;

use App\Models\AppliancePerson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAppliancePersonRequest extends FormRequest {
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
            // ID of the MPM user recording the sale.
            'user_id' => ['required', 'integer'],
            // Payment plan of the sale. Defaults to `installment`.
            'payment_type' => ['sometimes', 'nullable', Rule::in([AppliancePerson::PAYMENT_TYPE_INSTALLMENT, AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE])],
            // Total cost of the sale. Required for `installment` sales and may differ from the appliance's list price.
            'cost' => ['required_unless:payment_type,'.AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE, 'nullable', 'numeric', 'min:0'],
            // Number of installment rates. Required for `installment` sales.
            'rate' => ['required_unless:payment_type,'.AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE, 'nullable', 'integer', 'min:0'],
            // Installment schedule. Required for `installment` sales.
            'rate_type' => ['required_unless:payment_type,'.AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE, 'nullable', Rule::in(['monthly', 'weekly'])],
            // Down payment amount. `0` (or omitted) for no down payment.
            'down_payment' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            // Serial number of the device to assign to the person as part of the sale.
            'device_serial' => ['sometimes', 'nullable', 'string'],
            // Device location as `latitude,longitude`. Used when a `device_serial` is given.
            'points' => ['sometimes', 'nullable', 'string'],
            // Payment provider for the down payment. `0` books it as a cash payment, any other value must be the ID of an installed payment provider plugin.
            'payment_provider' => ['sometimes', 'nullable', 'integer'],
            'address' => ['sometimes', 'nullable', 'array'],
            // Phone number the down payment transaction is registered under.
            'address.phone' => ['sometimes', 'nullable', 'string'],
            // Minimum accepted payment amount. Only used for `energy_service` sales.
            'minimum_payable_amount' => ['sometimes', 'nullable', 'integer', 'min:0'],
            // Price per day. Only used for `energy_service` sales.
            'price_per_day' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }
}
