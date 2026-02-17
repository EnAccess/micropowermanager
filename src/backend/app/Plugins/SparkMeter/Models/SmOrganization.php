<?php

namespace App\Plugins\SparkMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $organization_id
 * @property string      $code
 * @property string      $display_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SmOrganization extends BaseModel {
    protected $table = 'sm_organizations';
}
