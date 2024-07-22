<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 20.08.18
 * Time: 14:58.
 */

namespace Inensus\MesombPaymentProvider\Models;

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

    public function resolveChildRouteBinding($childType, $value, $field)
    {
        // TODO: Implement resolveChildRouteBinding() method.
    }
}
