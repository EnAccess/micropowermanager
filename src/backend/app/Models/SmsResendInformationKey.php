<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $key
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SmsResendInformationKey extends BaseModel {
    /** @use HasFactory<Factory<SmsResendInformationKey>> */
    use HasFactory;

    protected $table = 'sms_resend_information_keys';
}
