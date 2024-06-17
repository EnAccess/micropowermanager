<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Restriction.
 *
 * @property int    $id
 * @property string $target
 * @property int    $default
 * @property int    $limit
 */
class Restriction extends BaseModel
{
    public function upgrades(): HasMany
    {
        return $this->hasMany(Upgrade::class);
    }
}
