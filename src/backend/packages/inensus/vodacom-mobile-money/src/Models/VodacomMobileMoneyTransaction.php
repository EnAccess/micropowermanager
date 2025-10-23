<?php

namespace Inensus\VodacomMobileMoney\Models;

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
class VodacomMobileMoneyTransaction extends BaseModel {
    protected $table = 'vodacom_mobile_money_transactions';
}
