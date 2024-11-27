<?php

namespace Inensus\BulkRegistration\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model {
    protected $guarded = ['id'];
    public static $rules = [];

    public function resolveChildRouteBinding($childType, $value, $field) {
        // TODO: Implement resolveChildRouteBinding() method.
    }
}
