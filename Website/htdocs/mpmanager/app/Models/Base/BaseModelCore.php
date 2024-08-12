<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;

/**
 * Base model for models which are implemented on the MPM core database.
 *
 * Only applies to very few common, high-level configuration entities in
 * the entire MPM-instance like database and plugin configuration.
 */
class BaseModelCore extends Model
{
    protected $guarded = ['id'];
    public static $rules = [];

    public function __construct(array $attributes = [])
    {
        $this->setConnection('micro_power_manager');
        parent::__construct($attributes);
    }
}
