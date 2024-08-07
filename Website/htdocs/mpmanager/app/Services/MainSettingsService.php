<?php

namespace App\Services;

use App\Models\MainSettings;

class MainSettingsService implements IBaseService
{
    public function __construct(
        private MainSettings $mainSettings
    ) {
    }

    public function getById(int $id): MainSettings
    {
        throw new \Exception('Method getById() not yet implemented.');

        return new MainSettings();
    }

    public function create(array $data): MainSettings
    {
        throw new \Exception('Method create() not yet implemented.');

        return new MainSettings();
    }

    public function update($mainSettings, $mainSettingsData): MainSettings
    {
        $mainSettings->update($mainSettingsData);
        $mainSettings->fresh();

        return $mainSettings;
    }

    public function delete($model): ?bool
    {
        throw new \Exception('Method getById() not yet implemented.');
    }

    public function getAll($limit = null)
    {
        return $this->mainSettings->newQuery()->get();
    }
}
