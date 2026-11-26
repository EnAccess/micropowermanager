<?php

namespace App\Plugins\AirtelPaymentProvider\Models;

use App\Models\Transaction\BasePaymentProviderTransaction;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $interface_id
 * @property string      $business_number
 * @property string      $trans_id                      Airtel's REFERENCE2, sent with the process call
 * @property string      $tr_id                         Airtel's transaction id (REFERENCE1 / TXNID)
 * @property int         $status
 * @property string|null $manufacturer_transaction_type
 * @property int|null    $manufacturer_transaction_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AirtelTransaction extends BasePaymentProviderTransaction {
    public const RELATION_NAME = 'airtel_transaction';

    /** Airtel has confirmed the payment and MPM is still producing what was paid for. */
    public const int STATUS_PROCESSING = 0;

    /** MPM accepted the validation call; Airtel has not confirmed the payment yet. */
    public const int STATUS_VALIDATED = 2;

    protected $table = 'airtel_transactions';

    /** @var list<string> */
    protected $fillable = [
        'interface_id',
        'business_number',
        'trans_id',
        'tr_id',
        'status',
        'manufacturer_transaction_type',
        'manufacturer_transaction_id',
    ];

    public static function getTransactionName(): string {
        return self::RELATION_NAME;
    }
}
