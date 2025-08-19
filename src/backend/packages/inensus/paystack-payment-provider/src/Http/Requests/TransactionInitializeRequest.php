<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Inensus\PaystackPaymentProvider\Models\PaystackTransaction;

class TransactionInitializeRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'amount' => 'required|numeric|min:0',
            'meter_serial' => 'required|string',
            'customer_id' => 'required|integer',
            'currency' => 'string|in:NGN,USD,GHS,KES,ZAR',
        ];
    }

    public function getPaystackTransaction(): PaystackTransaction {
        $transaction = new PaystackTransaction();
        $transaction->setAmount($this->input('amount'));
        $transaction->setMeterSerial($this->input('meter_serial'));
        $transaction->setCustomerId($this->input('customer_id'));
        $transaction->setCurrency($this->input('currency', 'NGN'));
        $transaction->setStatus(PaystackTransaction::STATUS_REQUESTED);

        return $transaction;
    }
}
