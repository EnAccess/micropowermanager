<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 29.05.18
 * Time: 10:57.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * This model handles models which are shared across the base and customer databases.
 */
class HybridModel extends Model
{
    protected $guarded = ['id'];
    public static $rules = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        if (config()->get('database.connections.shard')) {
            $this->setConnection('shard');
        }
    }
}
