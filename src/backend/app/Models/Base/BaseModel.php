<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;

/**
 * Base model for models which are implemented on tenant databases.
 *
 * This applies to all models in MPM which implement the business
 * logic and define interaction between entities.
 * "Most" models will extend this base model.
 */
abstract class BaseModel extends Model {
    protected $guarded = ['id'];

    /** @var array<string, string|array<string>> */
    public static $rules = [];

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = []) {
        $this->setConnection('tenant');

        parent::__construct($attributes);
    }

    public function resolveChildRouteBinding($childType, $value, $field) {
        // TODO: Implement resolveChildRouteBinding() method.
        throw new \Exception('Method resolveChildRouteBinding() not yet implemented.');
    }
}
