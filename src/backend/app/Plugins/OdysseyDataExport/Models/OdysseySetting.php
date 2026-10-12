<?php

namespace App\Plugins\OdysseyDataExport\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int                          $id
 * @property OdysseyFinancingProgram|null $financing_id
 * @property Carbon|null                  $created_at
 * @property Carbon|null                  $updated_at
 */
class OdysseySetting extends BaseModel {
    protected $table = 'odyssey_settings';

    protected $casts = [
        'financing_id' => OdysseyFinancingProgram::class,
    ];
}
