<?php

declare(strict_types=1);

namespace Inensus\WaveMoneyPaymentProvider\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionInitializeRequest extends FormRequest {
    private const BODY_PARAM_METER_SERIAL = 'meterSerial';
    private const BODY_PARAM_AMOUNT = 'amount';

    public function rules(): array {
        return [
            self::BODY_PARAM_METER_SERIAL => 'required',
            self::BODY_PARAM_AMOUNT => 'required|numeric',
        ];
    }
}
