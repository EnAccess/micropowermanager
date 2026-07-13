<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Http\Requests;

use App\Enums\DeviceType;
use App\Plugins\PesapalPaymentProvider\Models\PesapalTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Ramsey\Uuid\Uuid;

class TransactionInitializeRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array {
        $supportedCurrencies = config('pesapal-payment-provider.currency.supported', ['KES', 'UGX', 'TZS', 'USD']);

        return [
            'amount' => ['required', 'numeric', 'min:1'],
            'device_serial' => ['required', 'string'],
            'customer_id' => ['required', 'integer'],
            'currency' => ['nullable', 'string', 'in:'.implode(',', $supportedCurrencies)],
            'device_type' => ['nullable', 'string', Rule::in([DeviceType::Meter->value, DeviceType::SolarHomeSystem->value])],
        ];
    }

    public function getPesapalTransaction(): PesapalTransaction {
        $transaction = new PesapalTransaction();
        $transaction->amount = (float) $this->input('amount');
        $transaction->serial_id = $this->input('device_serial');
        $transaction->customer_id = (int) $this->input('customer_id');
        $transaction->currency = $this->input('currency', config('pesapal-payment-provider.currency.default', 'KES'));
        $transaction->status = PesapalTransaction::STATUS_REQUESTED;
        $deviceType = $this->input('device_type');
        if (is_string($deviceType) && $deviceType !== '') {
            $transaction->device_type = $deviceType;
        }
        $transaction->order_id = Uuid::uuid4()->toString();
        $transaction->reference_id = Uuid::uuid4()->toString();

        return $transaction;
    }
}
