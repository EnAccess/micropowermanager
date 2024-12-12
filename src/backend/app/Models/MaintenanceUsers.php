<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Inensus\Ticket\Models\Ticket;

class MaintenanceUsers extends BaseModel {
    use HasFactory;

    public const RELATION_NAME = 'maintenance_user';

    public function person(): BelongsTo {
        return $this->belongsTo(Person::class);
    }

    public function miniGrid(): BelongsTo {
        return $this->belongsTo(MiniGrid::class, 'mini_grid_id', 'id');
    }

    public function tickets(): MorphMany {
        return $this->morphMany(Ticket::class, 'owner');
    }
}
