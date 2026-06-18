<?php

namespace App\Plugins\VodacomMzPaymentProvider\Models;

use App\Models\Transaction\BasePaymentProviderTransaction;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $serialNumber
 * @property float       $amount
 * @property string      $payerPhoneNumber stored in E.164 format, e.g. "+258123456789"
 * @property string      $referenceId
 * @property int         $status
 * @property string|null $conversationId   OpenAPI output_ConversationID, returned once the C2B push is confirmed
 * @property string|null $transactionId    OpenAPI output_TransactionID, returned once the C2B push is confirmed
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class VodacomMzTransaction extends BasePaymentProviderTransaction {
    public const RELATION_NAME = 'vodacom_mz_transaction';

    public const STATUS_REQUESTED = 0;
    public const STATUS_FAILED = -1;
    public const STATUS_SUCCESS = 1;
    public const STATUS_COMPLETED = 2;
    public const STATUS_ABANDONED = 3;

    protected $table = 'vodacom_mz_transactions';

    public static function getTransactionName(): string {
        return self::RELATION_NAME;
    }
}
