<?php

declare(strict_types=1);

namespace Inensus\WaveMoneyPaymentProvider\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Inensus\WaveMoneyPaymentProvider\Modules\Api\Data\TransactionCallbackData;

class TransactionInitializeRequest extends FormRequest
{
    private const BODY_PARAM_METER_SERIAL = 'status';


    public function getMeterSerial():string
    {
        return $this->input(self::BODY_PARAM_METER_SERIAL);
    }

    public function rules(): array
    {
        return [
            self::BODY_PARAM_METER_SERIAL => 'required',
        ];
    }
}
