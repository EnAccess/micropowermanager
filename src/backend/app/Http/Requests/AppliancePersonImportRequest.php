<?php

namespace App\Http\Requests;

use App\Models\AppliancePerson;
use App\Services\ImportServices\AppliancePersonImportItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppliancePersonImportRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * Financial fields are validated loosely here (they may arrive as display
     * strings like "1,500") and normalized in {@see items()}. The per-payment-type
     * requirements — installments need a cost and rate count, and the customer and
     * appliance must already exist — are enforced per row by the import service so a
     * single bad row fails on its own instead of rejecting the whole batch.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'data' => ['required', 'array', 'list'],
            'data.*.customer_name' => ['required', 'string', 'min:1'],
            'data.*.customer_surname' => ['sometimes', 'nullable', 'string'],
            'data.*.appliance_name' => ['required', 'string', 'min:1'],
            'data.*.payment_type' => ['sometimes', 'nullable', Rule::in([AppliancePerson::PAYMENT_TYPE_INSTALLMENT, AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE])],
            'data.*.total_cost' => ['sometimes', 'nullable'],
            'data.*.rate_count' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'data.*.rate_type' => ['sometimes', 'nullable', Rule::in(['monthly', 'weekly'])],
            'data.*.down_payment' => ['sometimes', 'nullable'],
            'data.*.first_payment_date' => ['sometimes', 'nullable', 'date'],
            'data.*.device_serial' => ['sometimes', 'nullable', 'string'],
            'data.*.minimum_payable_amount' => ['sometimes', 'nullable'],
            'data.*.price_per_day' => ['sometimes', 'nullable'],
        ];
    }

    /**
     * @return list<AppliancePersonImportItem>
     */
    public function items(): array {
        $creatorId = (int) auth('api')->id();

        return array_map(fn (array $item): AppliancePersonImportItem => new AppliancePersonImportItem(
            customerName: $item['customer_name'],
            customerSurname: $item['customer_surname'] ?? '',
            applianceName: $item['appliance_name'],
            paymentType: $item['payment_type'] ?? AppliancePerson::PAYMENT_TYPE_INSTALLMENT,
            totalCost: $this->parseInt($item['total_cost'] ?? null),
            rateCount: isset($item['rate_count']) ? (int) $item['rate_count'] : null,
            rateType: $item['rate_type'] ?? null,
            downPayment: $this->parseFloat($item['down_payment'] ?? null),
            firstPaymentDate: $item['first_payment_date'] ?? null,
            deviceSerial: $item['device_serial'] ?? null,
            minimumPayableAmount: $this->parseInt($item['minimum_payable_amount'] ?? null),
            pricePerDay: $this->parseInt($item['price_per_day'] ?? null),
            creatorId: $creatorId,
        ), $this->validated('data'));
    }

    private function parseInt(mixed $value): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            return (int) str_replace([',', ' '], '', $value);
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function parseFloat(mixed $value): ?float {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            return (float) str_replace([',', ' '], '', $value);
        }

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array {
        return [
            'data.required' => 'The data field is required.',
            'data.array' => 'The data must be an array.',
            'data.*.customer_name.required' => 'Each AppliancePerson must reference a customer name.',
            'data.*.appliance_name.required' => 'Each AppliancePerson must reference an appliance name.',
        ];
    }
}
