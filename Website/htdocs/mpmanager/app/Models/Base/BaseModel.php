<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 29.05.18
 * Time: 10:57.
 */

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    protected $guarded = ['id'];
    public static $rules = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setConnection('shard');
    }
}
