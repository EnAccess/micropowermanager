<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmsResendInformationKey extends BaseModel {
    use HasFactory;
    protected $table = 'sms_resend_information_keys';
}
