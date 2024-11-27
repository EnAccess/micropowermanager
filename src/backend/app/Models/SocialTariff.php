<?php

namespace App\Models;

use App\Models\Base\BaseModel;

/**
 * Class SocialTariff.
 *
 * @property int $id
 * @property int $tariff_id
 * @property int $daily_allowance
 * @property int $price
 * @property int $initial_energy_budget
 * @property int $maximum_stacked_energy
 */
class SocialTariff extends BaseModel {
    protected $guarded = [];
}
