<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\ConnectionType;
use Illuminate\Database\Eloquent\Model;

class SteamaUserType extends BaseModel
{
    protected $table = 'steama_user_types';

    public function mpmConnectionType()
    {
        return $this->belongsTo(ConnectionType::class, 'mpm_connection_type_id');
    }
}
