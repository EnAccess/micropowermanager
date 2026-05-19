<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Http\Requests;

use App\Plugins\PesapalPaymentProvider\Models\PesapalTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class TransactionInitializeRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array {
        $supportedCurrencies = config('pesapal-payment-provider.currency.supported', ['KES', 'UGX', 'TZS', 'USD']);

        return [
            'amount' => ['required', 'numeric', 'min:1'],
            'device_serial' => ['required', 'string'],
            'customer_id' => ['required', 'integer'],
            'currency' => ['nullable', 'string', 'in:'.implode(',', $supportedCurrencies)],
            'device_type' => ['nullable', 'string', 'in:meter,solar_home_system,shs'],
        ];
    }

    public function getPesapalTransaction(): PesapalTransaction {
        $transaction = new PesapalTransaction();
        $transaction->setAmount((float) $this->input('amount'));
        $transaction->setDeviceSerial($this->input('device_serial'));
        $transaction->setCustomerId((int) $this->input('customer_id'));
        $transaction->setCurrency($this->input('currency', config('pesapal-payment-provider.currency.default', 'KES')));
        $transaction->setStatus(PesapalTransaction::STATUS_REQUESTED);
        $deviceType = $this->input('device_type');
        if (is_string($deviceType) && $deviceType !== '') {
            $transaction->setDeviceType($deviceType);
        }
        $transaction->setOrderId(Uuid::uuid4()->toString());
        $transaction->setReferenceId(Uuid::uuid4()->toString());

        return $transaction;
    }
}
