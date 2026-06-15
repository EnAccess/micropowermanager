<?php

declare(strict_types=1);

namespace App\Plugins\PaystackPaymentProvider\Http\Requests;

use App\Plugins\PaystackPaymentProvider\Models\PaystackTransaction;
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
        return [
            'amount' => ['required', 'numeric', 'min:0'],
            'device_serial' => ['required', 'string'],
            'customer_id' => ['required', 'integer'],
            'currency' => ['string', 'in:NGN,GHS,KES'],
            'device_type' => ['string', 'in:meter,solar_home_system'],
        ];
    }

    public function getPaystackTransaction(): PaystackTransaction {
        $transaction = new PaystackTransaction();
        $transaction->setAmount($this->input('amount'));
        $transaction->serial_id = $this->input('device_serial');
        $transaction->customer_id = $this->input('customer_id');
        $transaction->currency = $this->input('currency', 'NGN');
        $transaction->status = PaystackTransaction::STATUS_REQUESTED;
        $transaction->device_type = $this->input('device_type');
        $transaction->order_id = Uuid::uuid4()->toString();
        $transaction->reference_id = Uuid::uuid4()->toString();

        return $transaction;
    }
}
