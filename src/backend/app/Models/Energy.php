<?php

namespace App\Models;

use App\Models\Base\BaseModel;

/**
 * Class Energy.
 *
 * @property int $meter_id
 * @property int mini_grid_id
 * @property int node_id
 * @property int device_id
 * @property int total_energy
 * @property int read_out
 * @property int used_energy_since_last
 * @property int active
 */
class Energy extends BaseModel
{
}
