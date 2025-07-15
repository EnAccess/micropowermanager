<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Model;

/**
 * Base model for models which are implemented on the MPM central database.
 *
 * Only applies to very few common, high-level configuration entities in
 * the entire MPM-instance like database and plugin configuration.
 */
abstract class BaseModelCentral extends Model {
    protected $guarded = ['id'];

    /** @var array<string, string> */
    public static $rules = [];

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = []) {
        $this->setConnection('micro_power_manager');

        parent::__construct($attributes);
    }
}
