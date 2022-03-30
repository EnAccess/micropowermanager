<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MapSettings
 *
 * @package  App\Models
 * @property int $id
 * @property float $zoom
 * @property float $latitude
 * @property float $longitude
 */

class MapSettings extends BaseModel
{
    protected $connection = 'test_company_db';
}
