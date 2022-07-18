<?php

namespace App\Services;

use App\Models\MainSettings;

class MainSettingsService implements IBaseService
{

    public function __construct(private MainSettings $mainSettings)
    {
    }

    public function getById($id)
    {
        // TODO: Implement getById() method.
    }

    public function create($data)
    {
        // TODO: Implement create() method.
    }

    public function update($mainSettings, $mainSettingsData)
    {
        $mainSettings->update($mainSettingsData);
        $mainSettings->fresh();

        return $mainSettings;
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }

    public function getAll($limit = null)
    {
       return $this->mainSettings->newQuery()->get();
    }
}