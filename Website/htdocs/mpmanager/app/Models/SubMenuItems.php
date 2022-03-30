<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubMenuItems extends BaseModel
{
    protected $connection = 'test_company_db';
    public function menuItems(): BelongsTo
    {
        return $this->belongsTo(MenuItems::class);
    }
}
