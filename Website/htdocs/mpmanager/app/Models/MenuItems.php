<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItems extends BaseModel
{
    protected $connection = 'test_company_db';
    public function subMenuItems(): HasMany
    {
        return $this->hasMany(SubMenuItems::class, 'parent_id');
    }
}
