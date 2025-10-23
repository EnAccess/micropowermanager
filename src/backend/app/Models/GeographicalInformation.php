<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Database\Factories\GeographicalInformationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Class GeographicalInformation.
 *
 * @property      int         $id
 * @property      int         $owner_id
 * @property      string      $owner_type
 * @property      string      $points
 * @property      Carbon|null $created_at
 * @property      Carbon|null $updated_at
 * @property-read Model       $owner
 */
class GeographicalInformation extends BaseModel {
    /** @use HasFactory<GeographicalInformationFactory> */
    use HasFactory;

    protected $table = 'geographical_informations';

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo {
        return $this->morphTo();
    }
}
