<?php

namespace App\Services\Interfaces;

use Illuminate\Database\Eloquent\Model;

/**
 * Interface that defines Assiciative services. What this means, is unclear.
 *
 * @template T of Model
 */
interface IAssociative {
    public function make(array $data): Model;

    /**
     * @param T $model
     */
    public function save(Model $model): bool;
}
