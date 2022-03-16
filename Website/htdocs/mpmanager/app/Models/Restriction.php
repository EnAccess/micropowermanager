<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Restriction
 *
 * @package App\Models
 *
 * @property int $id
 * @property string $target
 * @property int $default
 * @property int $limit
 */
class Restriction extends Model
{
    public function upgrades(): HasMany
    {
        return $this->hasMany(Upgrade::class);
    }
}
