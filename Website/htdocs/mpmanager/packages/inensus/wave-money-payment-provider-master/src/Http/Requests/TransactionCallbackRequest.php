<?php

declare(strict_types=1);

namespace Inensus\WaveMoneyPaymentProvider\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Inensus\WaveMoneyPaymentProvider\Modules\Api\Data\TransactionCallbackData;

class TransactionCallbackRequest extends FormRequest
{
    private const BODY_PARAM_STATUS = 'status';
    private const BODY_PARAM_MERCHANT_ID = 'status';
    private const BODY_PARAM_ORDER_ID = 'merchantId';
    private const BODY_PARAM_MERCHANT_REFERENCE_ID = 'merchantReferenceId';
    private const BODY_PARAM_FRONTEND_RESULT_URL = 'frontendResultUrl';
    private const BODY_PARAM_BACKEND_RESULT_URL = 'backendResultUrl';
    private const BODY_PARAM_INITIATOR_MSISDN = 'initiatorMsisdn';
    private const BODY_PARAM_AMOUNT = 'amount';
    private const BODY_PARAM_TIME_TO_LIVE_SECONDS = 'timeToLiveSeconds';
    private const BODY_PARAM_PAYMENT_DESCRIPTION = 'paymentDescription';
    private const BODY_PARAM_CURRENCY = 'currency';
    private const BODY_PARAM_HASH_VALUE = 'hashValue';
    private const BODY_PARAM_TRANSACTION_ID = 'transactionId';
    private const BODY_PARAM_PAYMENT_REQUEST_ID = 'paymentRequestId';
    private const BODY_PARAM_REQUEST_TIME = 'requestTime';
    private const BODY_PARAM_ADDITIONAL_FIELD_1 = 'additionalField1';
    private const BODY_PARAM_ADDITIONAL_FIELD_2 = 'additionalField2';
    private const BODY_PARAM_ADDITIONAL_FIELD_3 = 'additionalField3';
    private const BODY_PARAM_ADDITIONAL_FIELD_4 = 'additionalField4';
    private const BODY_PARAM_ADDITIONAL_FIELD_5 = 'additionalField5';


    public function getMappedObject(): TransactionCallbackData
    {
        return new TransactionCallbackData(
            $this->input(self::BODY_PARAM_STATUS),
            $this->input(self::BODY_PARAM_MERCHANT_ID),
            $this->input(self::BODY_PARAM_ORDER_ID),
            $this->input(self::BODY_PARAM_MERCHANT_REFERENCE_ID),
            $this->input(self::BODY_PARAM_FRONTEND_RESULT_URL),
            $this->input(self::BODY_PARAM_BACKEND_RESULT_URL),
            $this->input(self::BODY_PARAM_INITIATOR_MSISDN),
            $this->input(self::BODY_PARAM_AMOUNT),
            $this->input(self::BODY_PARAM_TIME_TO_LIVE_SECONDS),
            $this->input(self::BODY_PARAM_PAYMENT_DESCRIPTION),
            $this->input(self::BODY_PARAM_CURRENCY),
            $this->input(self::BODY_PARAM_HASH_VALUE),
            $this->input(self::BODY_PARAM_TRANSACTION_ID),
            $this->input(self::BODY_PARAM_PAYMENT_REQUEST_ID),
            $this->input(self::BODY_PARAM_REQUEST_TIME),
            $this->input(self::BODY_PARAM_ADDITIONAL_FIELD_1),
            $this->input(self::BODY_PARAM_ADDITIONAL_FIELD_2),
            $this->input(self::BODY_PARAM_ADDITIONAL_FIELD_3),
            $this->input(self::BODY_PARAM_ADDITIONAL_FIELD_4),
            $this->input(self::BODY_PARAM_ADDITIONAL_FIELD_5),

        );
    }
}
