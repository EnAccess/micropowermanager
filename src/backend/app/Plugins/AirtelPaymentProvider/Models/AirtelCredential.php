<?php

namespace App\Plugins\AirtelPaymentProvider\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string|null $jwt_secret
 * @property string|null $phone_prefix country calling code of the payers, e.g. "+255"
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AirtelCredential extends BaseModel {
    protected $table = 'airtel_credentials';
}
