<?php

declare(strict_types=1);

namespace App\Plugins\WaveMoneyPaymentProvider\Http\Requests;

use App\Plugins\WaveMoneyPaymentProvider\Modules\Api\Data\TransactionCallbackData;
use Illuminate\Http\Request;

class TransactionCallbackRequestMapper {
    private const string BODY_PARAM_STATUS = 'status';
    private const string BODY_PARAM_MERCHANT_ID = 'merchantId';
    private const string BODY_PARAM_ORDER_ID = 'orderId';
    private const string BODY_PARAM_MERCHANT_REFERENCE_ID = 'merchantReferenceId';
    private const string BODY_PARAM_FRONTEND_RESULT_URL = 'frontendResultUrl';
    private const string BODY_PARAM_BACKEND_RESULT_URL = 'backendResultUrl';
    private const string BODY_PARAM_INITIATOR_MSISDN = 'initiatorMsisdn';
    private const string BODY_PARAM_AMOUNT = 'amount';
    private const string BODY_PARAM_TIME_TO_LIVE_SECONDS = 'timeToLiveSeconds';
    private const string BODY_PARAM_PAYMENT_DESCRIPTION = 'paymentDescription';
    private const string BODY_PARAM_CURRENCY = 'currency';
    private const string BODY_PARAM_HASH_VALUE = 'hashValue';
    private const string BODY_PARAM_TRANSACTION_ID = 'transactionId';
    private const string BODY_PARAM_PAYMENT_REQUEST_ID = 'paymentRequestId';
    private const string BODY_PARAM_REQUEST_TIME = 'requestTime';
    private const string BODY_PARAM_ADDITIONAL_FIELD_1 = 'additionalField1';
    private const string BODY_PARAM_ADDITIONAL_FIELD_2 = 'additionalField2';
    private const string BODY_PARAM_ADDITIONAL_FIELD_3 = 'additionalField3';
    private const string BODY_PARAM_ADDITIONAL_FIELD_4 = 'additionalField4';
    private const string BODY_PARAM_ADDITIONAL_FIELD_5 = 'additionalField5';

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
            $request->integer(self::BODY_PARAM_TIME_TO_LIVE_SECONDS),
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
