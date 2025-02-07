<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmsBody extends BaseModel {
    use HasFactory;
    protected $table = 'sms_bodies';
}
