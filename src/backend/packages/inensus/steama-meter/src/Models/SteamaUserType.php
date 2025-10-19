<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\ConnectionType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ConnectionType $mpmConnectionType
 */
class SteamaUserType extends \App\Models\Base\BaseModel {
    protected $table = 'steama_user_types';

    public function mpmConnectionType(): BelongsTo {
        return $this->belongsTo(ConnectionType::class, 'mpm_connection_type_id');
    }
}
