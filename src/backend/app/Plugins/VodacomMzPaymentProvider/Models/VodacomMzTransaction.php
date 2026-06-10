<?php

namespace App\Plugins\VodacomMzPaymentProvider\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $serialNumber
 * @property float       $amount
 * @property string      $payerPhoneNumber
 * @property string      $referenceId
 * @property string      $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class VodacomMzTransaction extends BaseModel {
    protected $table = 'vodacom_mz_transactions';

    public const STATUS_REQUESTED = 0;
    public const STATUS_FAILED = -1;
    public const STATUS_SUCCESS = 1;
    public const STATUS_COMPLETED = 2;
    public const STATUS_ABANDONED = 3;
}
