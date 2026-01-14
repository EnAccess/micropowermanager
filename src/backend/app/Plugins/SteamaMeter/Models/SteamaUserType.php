<?php

namespace App\Plugins\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\ConnectionType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int                 $id
 * @property      int                 $mpm_connection_type_id
 * @property      string              $name
 * @property      string              $syntax
 * @property      Carbon|null         $created_at
 * @property      Carbon|null         $updated_at
 * @property-read ConnectionType|null $mpmConnectionType
 */
class SteamaUserType extends BaseModel {
    protected $table = 'steama_user_types';

    /**
     * @return BelongsTo<ConnectionType, $this>
     */
    public function mpmConnectionType(): BelongsTo {
        return $this->belongsTo(ConnectionType::class, 'mpm_connection_type_id');
    }
}
