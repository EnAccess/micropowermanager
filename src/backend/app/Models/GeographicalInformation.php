<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class GeographicalInformation.
 *
 * @property string $points
 */
class GeographicalInformation extends BaseModel {
    use HasFactory;

    protected $table = 'geographical_informations';

    public function owner(): MorphTo {
        return $this->morphTo();
    }
}
