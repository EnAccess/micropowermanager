<?php

namespace Inensus\MesombPaymentProvider\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model {
    protected $guarded = ['id'];

    /** @var array<string, string> */
    public static $rules = [];

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = []) {
        parent::__construct($attributes);
        $this->setConnection('tenant');
    }

    public function resolveChildRouteBinding($childType, $value, $field) {
        // TODO: Implement resolveChildRouteBinding() method.
        throw new \Exception('Method resolveChildRouteBinding() not yet implemented.');
    }
}
