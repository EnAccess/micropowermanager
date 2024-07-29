<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 29.05.18
 * Time: 10:57.
 */

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;

/**
 * This model handles models which are shared across the base and customer databases.
 */
class MasterModel extends Model
{
    protected $guarded = ['id'];
    public static $rules = [];

    public function __construct(array $attributes = [])
    {
        $this->setConnection('micro_power_manager');
        parent::__construct($attributes);
    }
}
