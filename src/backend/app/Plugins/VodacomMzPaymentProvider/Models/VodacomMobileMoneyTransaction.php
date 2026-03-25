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
class VodacomMzPaymentProviderTransaction extends BaseModel {
    protected $table = 'vodacom_mz_transactions';
}
