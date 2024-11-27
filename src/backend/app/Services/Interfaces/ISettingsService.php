<?php

namespace App\Services\Interfaces;

use Illuminate\Database\Eloquent\Model;

/**
 * Interface that defines meaningful operations on settings.
 *
 * @template T of Model
 */
interface ISettingsService {
    /** @return T|null */
    public function get(): ?Model;

    /**
     * @param T $model
     *
     * @return T
     */
    public function update(Model $model, array $data): Model;
}
