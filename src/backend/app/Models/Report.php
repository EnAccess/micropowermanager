<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * Class Report.
 *
 * @property int         $id
 * @property string      $name
 * @property string      $path
 * @property string      $type
 * @property string      $date       `date` column is actually a stringified `date_range`
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Report extends BaseModel {}
