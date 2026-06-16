<?php

namespace App\Services;

use App\Models\MainSettings;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;

/**
 * @phpstan-type MainSettingsData array{
 *     name?: string,
 *     value?: string,
 *     description?: string,
 *     is_active?: bool,
 *     created_at?: string,
 *     updated_at?: string
 * }
 *
 * @implements IBaseService<MainSettings>
 */
// FIXME: Should this not be a ISettingsService?
class MainSettingsService implements IBaseService {
    /** @use HasCrudOperations<MainSettings> */
    use HasCrudOperations;

    public function __construct(
        private MainSettings $mainSettings,
    ) {}

    protected function crudModel(): MainSettings {
        return $this->mainSettings;
    }

    public function getById(int $id): MainSettings {
        return $this->mainSettings->newQuery()->findOrFail($id);
    }
}
