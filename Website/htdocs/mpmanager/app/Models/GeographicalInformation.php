<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class GeographicalInformation.
 *
 * @property string $points
 */
class GeographicalInformation extends BaseModel
{
    protected $table = 'geographical_informations';

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
