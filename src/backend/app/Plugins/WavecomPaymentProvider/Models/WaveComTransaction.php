<?php

declare(strict_types=1);

namespace App\Plugins\WavecomPaymentProvider\Models;

use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\TransactionConflicts;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property      int                                   $id
 * @property      string                                $transaction_id
 * @property      string                                $sender
 * @property      string                                $message
 * @property      int                                   $amount
 * @property      int                                   $status
 * @property      Carbon|null                           $created_at
 * @property      Carbon|null                           $updated_at
 * @property      string|null                           $manufacturer_transaction_type
 * @property      int|null                              $manufacturer_transaction_id
 * @property-read Collection<int, TransactionConflicts> $conflicts
 * @property-read Model|null                            $manufacturerTransaction
 * @property-read Transaction|null                      $transaction
 */
class WaveComTransaction extends BasePaymentProviderTransaction {
    protected $table = 'wavecom_transactions';
    public const RELATION_NAME = 'wavecom_transaction';
    public const STATUS_SUCCESS = 1;
    public const STATUS_CANCELLED = -1;

    public function getManufacturerTransferType(): ?string {
        // TODO add type API/IMPORT
        return 'WaveCom';
    }

    public static function getTransactionName(): string {
        return self::RELATION_NAME;
    }
}
