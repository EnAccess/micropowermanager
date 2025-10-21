<?php

namespace Inensus\SwiftaPaymentProvider\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string|null $token
 * @property int|null    $expire_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SwiftaAuthentication extends BaseModel {
    protected $table = 'swifta_authentication';
}
