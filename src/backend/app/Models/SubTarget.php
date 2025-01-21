<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class SubTarget.
 *
 * @property int $id
 * @property int $target_id
 * @property int $connection_id
 * @property int $revenue
 * @property int new_connections
 */
class SubTarget extends BaseModel {
    use HasFactory;

    /**
     * @return BelongsTo
     */
    public function target(): BelongsTo {
        return $this->belongsTo(Target::class);
    }

    /**
     * @return BelongsTo
     */
    public function connectionType(): BelongsTo {
        return $this->belongsTo(ConnectionGroup::class, 'connection_id');
    }
}
