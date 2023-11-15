<?php

namespace App\Models;

use App\Models\Address\Address;
use App\Models\Meter\MeterParameter;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class GeographicalInformation
 *
 * @package App\Models
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
