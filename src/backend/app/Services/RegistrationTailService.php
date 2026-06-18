<?php

namespace App\Services;

use App\Models\MpmPlugin;
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
        return $this->create($mpmPlugin->toRegistrationTailEntry());
    }

    public function removeMpmPluginFromRegistrationTail(MpmPlugin $mpmPlugin): void {
        $this->crudModel()->newQuery()->where('tag', $mpmPlugin->name)->delete();
    }

    public function adjustStep(string $tag): void {
        $this->crudModel()->newQuery()->where('tag', $tag)->update(['adjusted' => true]);
    }
}
