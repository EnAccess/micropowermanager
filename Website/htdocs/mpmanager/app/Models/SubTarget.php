<?php

namespace App\Models;

use App\Models\ConnectionType;
use App\Models\Target;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class SubTarget
 *
 * @package App
 *
 * @property int $id
 * @property int $target_id
 * @property int $connection_id
 * @property int $revenue
 * @property int new_connections
 */
class SubTarget extends Model
{


    /**
     * @return BelongsTo
     */
    public function target(): BelongsTo
    {
        return $this->belongsTo(Target::class);
    }

    /**
     * @return BelongsTo
     */
    public function connectionType(): BelongsTo
    {
        return $this->belongsTo(ConnectionGroup::class, 'connection_id');
    }
}
