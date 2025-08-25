<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Database\Factories\SmsBodyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmsBody extends BaseModel {
    /** @use HasFactory<SmsBodyFactory> */
    use HasFactory;
    protected $table = 'sms_bodies';
}
