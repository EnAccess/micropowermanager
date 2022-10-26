<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MpmPlugin extends MasterModel
{
    use HasFactory;

    protected $table = 'mpm_plugins';

    //has many used
    public function plugins()
    {
        return $this->hasMany(Plugins::class);
    }
}
