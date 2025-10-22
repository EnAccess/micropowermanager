<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * Class MapSettings.
 *
 * @property int         $id
 * @property int         $zoom
 * @property float       $latitude
 * @property float       $longitude
 * @property string|null $provider
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class MapSettings extends BaseModel {}
