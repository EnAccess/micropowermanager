<?php

namespace App\Plugins\BulkRegistration\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property int         $user_id
 * @property string      $csv_filename
 * @property string      $csv_data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CsvData extends BaseModel {
    protected $table = 'bulk_registration_csv_datas';
}
