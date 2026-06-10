<?php

namespace App\Models\Transaction;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property      int                                   $id
 * @property      string                                $transaction_id
 * @property      string|null                           $description
 * @property      string|null                           $manufacturer_transaction_type
 * @property      int|null                              $manufacturer_transaction_id
 * @property      Carbon|null                           $created_at
 * @property      Carbon|null                           $updated_at
 * @property-read Model|null                            $manufacturerTransaction
 * @property-read Transaction|null                      $transaction
 */
class ThirdPartyTransaction extends BasePaymentProviderTransaction {
    public const RELATION_NAME = 'third_party_transaction';

    public static function getTransactionName(): string {
        return self::RELATION_NAME;
    }
}
