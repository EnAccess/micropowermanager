<?php

namespace App\Models;

use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceUsers extends BaseModel
{
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function miniGrid(): BelongsTo
    {
        return $this->belongsTo(MiniGrid::class, 'mini_grid_id', 'id');
    }
}
