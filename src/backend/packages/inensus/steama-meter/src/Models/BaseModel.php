<?php

namespace Inensus\SteamaMeter\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model {
    protected $guarded = ['id'];

    /** @var array<string, string|array<string>> */
    public static $rules = [];

    public function __construct(array $attributes = []) {
        parent::__construct($attributes);
        $this->setConnection('tenant');
    }

    public function resolveChildRouteBinding($childType, $value, $field) {
        // TODO: Implement resolveChildRouteBinding() method.
        throw new \Exception('Method resolveChildRouteBinding() not yet implemented.');
    }
}
