<?php

namespace App\Services;

use App\Models\MpmPlugin;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;

/**
 * @implements IBaseService<MpmPlugin>
 */
class MpmPluginService implements IBaseService {
    /** @use HasCrudOperations<MpmPlugin> */
    use HasCrudOperations;

    public function __construct(
        private MpmPlugin $mpmPlugin,
    ) {}

    protected function crudModel(): MpmPlugin {
        return $this->mpmPlugin;
    }

    /**
     * @param int|string $id
     */
    public function getById($id): MpmPlugin {
        return $this->mpmPlugin->newQuery()->findOrFail($id);
    }
}
