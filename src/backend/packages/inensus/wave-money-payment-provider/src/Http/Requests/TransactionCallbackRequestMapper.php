<?php

declare(strict_types=1);

namespace Inensus\WaveMoneyPaymentProvider\Http\Requests;

use Illuminate\Http\Request;
use Inensus\WaveMoneyPaymentProvider\Modules\Api\Data\TransactionCallbackData;

class TransactionCallbackRequestMapper {
    private const BODY_PARAM_STATUS = 'status';
    private const BODY_PARAM_MERCHANT_ID = 'merchantId';
    private const BODY_PARAM_ORDER_ID = 'orderId';
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

    public function getMappedObject(Request $request): TransactionCallbackData {
        return new TransactionCallbackData(
            $request->input(self::BODY_PARAM_STATUS),
            $request->input(self::BODY_PARAM_MERCHANT_ID),
            $request->input(self::BODY_PARAM_ORDER_ID),
            $request->input(self::BODY_PARAM_MERCHANT_REFERENCE_ID),
            $request->input(self::BODY_PARAM_FRONTEND_RESULT_URL),
            $request->input(self::BODY_PARAM_BACKEND_RESULT_URL),
            $request->input(self::BODY_PARAM_INITIATOR_MSISDN),
            floatval($request->input(self::BODY_PARAM_AMOUNT)),
            (int) $request->input(self::BODY_PARAM_TIME_TO_LIVE_SECONDS),
            $request->input(self::BODY_PARAM_PAYMENT_DESCRIPTION),
            $request->input(self::BODY_PARAM_CURRENCY),
            $request->input(self::BODY_PARAM_HASH_VALUE),
            $request->input(self::BODY_PARAM_TRANSACTION_ID),
            $request->input(self::BODY_PARAM_PAYMENT_REQUEST_ID),
            $request->input(self::BODY_PARAM_REQUEST_TIME),
            $request->input(self::BODY_PARAM_ADDITIONAL_FIELD_1),
            $request->input(self::BODY_PARAM_ADDITIONAL_FIELD_2),
            $request->input(self::BODY_PARAM_ADDITIONAL_FIELD_3),
            $request->input(self::BODY_PARAM_ADDITIONAL_FIELD_4),
            $request->input(self::BODY_PARAM_ADDITIONAL_FIELD_5),
        );
    }
}
