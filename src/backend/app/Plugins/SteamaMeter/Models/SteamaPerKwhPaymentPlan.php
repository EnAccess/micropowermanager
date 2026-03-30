<?php

namespace App\Plugins\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property float       $energy_price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SteamaPerKwhPaymentPlan extends BaseModel {
    protected $table = 'steama_per_kwh_payment_plans';
}
