<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItems extends BaseModel
{
    public function subMenuItems(): HasMany
    {
        return $this->hasMany(SubMenuItems::class, 'parent_id');
    }
}
