<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class GeographicalInformation.
 *
 * @property string $points
 * @property Model  $owner
 * @property strinf $owner_type
 */
class GeographicalInformation extends BaseModel {
    /** @use HasFactory<\Database\Factories\GeographicalInformationFactory> */
    use HasFactory;

    protected $table = 'geographical_informations';

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo {
        return $this->morphTo();
    }
}
