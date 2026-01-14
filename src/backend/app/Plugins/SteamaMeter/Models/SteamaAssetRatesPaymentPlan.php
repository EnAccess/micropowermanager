<?php

namespace App\Plugins\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property int         $mpm_asset_people_id
 * @property float       $down_payment
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SteamaAssetRatesPaymentPlan extends BaseModel {
    protected $table = 'steama_asset_rates_payment_plans';
}
