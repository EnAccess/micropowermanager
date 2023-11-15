<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Transaction\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PDO;

class Revenue extends BaseModel
{
   protected $table = 'revenues';
}
