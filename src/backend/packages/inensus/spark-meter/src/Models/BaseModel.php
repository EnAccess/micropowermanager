<?php

namespace Inensus\SparkMeter\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model {
    protected $guarded = ['id'];
    public static $rules = [];

    public function __construct(array $attributes = []) {
        parent::__construct($attributes);
        $this->setConnection('tenant');
    }

    public function resolveChildRouteBinding($childType, $value, $field) {
        // TODO: Implement resolveChildRouteBinding() method.
    }
}
