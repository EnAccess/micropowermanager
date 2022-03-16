<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 29.05.18
 * Time: 10:57
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    protected $guarded = ['id'];
    public static $rules = [];
}
