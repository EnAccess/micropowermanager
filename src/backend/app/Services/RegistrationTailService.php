<?php

namespace App\Services;

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

    public function addMpmPluginToRegistrationTail(string $mpmPlugin): RegistrationTail {
        return $this->create(['component' => $mpmPlugin, 'adjusted' => false]);
    }

    public function removeMpmPluginFromRegistrationTail(string $mpmPlugin): void {
        $this->crudModel()->newQuery()->where('component', $mpmPlugin)->delete();
    }

    public function adjustStep(string $mpmPlugin): void {
        $this->crudModel()->newQuery()->where('component', $mpmPlugin)->update(['adjusted' => true]);
    }
}
