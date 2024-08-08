<?php

namespace App\Services\Interfaces;

use Illuminate\Database\Eloquent\Model;

/**
 * @template T of Model
 */
interface IAssociative
{
    public function make(array $data): Model;

    /**
     * @param T $model
     */
    public function save(Model $model): bool;
}
