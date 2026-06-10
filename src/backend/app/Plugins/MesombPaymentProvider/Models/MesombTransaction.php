<?php

namespace App\Plugins\MesombPaymentProvider\Models;

use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property      int                                   $id
 * @property      string                                $pk
 * @property      string                                $type
 * @property      float                                 $amount
 * @property      float|null                            $fees
 * @property      string                                $b_party
 * @property      string                                $message
 * @property      string                                $service
 * @property      string|null                           $reference
 * @property      string                                $ts
 * @property      int                                   $direction
 * @property      Carbon|null                           $created_at
 * @property      Carbon|null                           $updated_at
 * @property-read Model                                 $manufacturerTransaction
 * @property-read Transaction|null                      $transaction
 */
class MesombTransaction extends BasePaymentProviderTransaction {
    protected $table = 'mesomb_transactions';
    public const RELATION_NAME = 'mesomb_transactions';

    public static function getTransactionName(): string {
        return self::RELATION_NAME;
    }
}
