<?php

namespace App\Services;

use App\Enums\MpmPlugin;
use App\Models\RegistrationTail;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;

/**
 * @implements IBaseService<RegistrationTail>
 */
class RegistrationTailService implements IBaseService {
    /** @use HasCrudOperations<RegistrationTail> */
    use HasCrudOperations;

    public function __construct(
        private RegistrationTail $registrationTail,
    ) {}

    protected function crudModel(): RegistrationTail {
        return $this->registrationTail;
    }

    public function addMpmPluginToRegistrationTail(MpmPlugin $mpmPlugin): RegistrationTail {
        return $this->create(['component' => $mpmPlugin->value, 'adjusted' => false]);
    }

    public function removeMpmPluginFromRegistrationTail(MpmPlugin $mpmPlugin): void {
        $this->crudModel()->newQuery()->where('component', $mpmPlugin->value)->delete();
    }

    public function adjustStep(MpmPlugin $mpmPlugin): void {
        $this->crudModel()->newQuery()->where('component', $mpmPlugin->value)->update(['adjusted' => true]);
    }
}
