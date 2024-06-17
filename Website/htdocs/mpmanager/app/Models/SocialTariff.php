<?php

namespace App\Models;

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
class SocialTariff extends BaseModel
{
    protected $guarded = [];
}
