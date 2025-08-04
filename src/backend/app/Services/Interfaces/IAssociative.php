<?php

namespace App\Services\Interfaces;

use Illuminate\Database\Eloquent\Model;

/**
 * Interface that defines Associative services. What this means, is unclear.
 *
 * @template T of Model
 */
interface IAssociative {
    /**
     * @param array<string, mixed> $data
     *
     * @return T
     */
    public function make(array $data): Model;

    /**
     * @param T $model
     */
    public function save(Model $model): bool;
}
