<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plugins extends Model
{
    protected $fillable = ['name', 'composer_name', 'description'];
}
