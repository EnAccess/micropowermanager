<?php

namespace App\Services;

use App\Models\MpmPlugin;

class MpmPluginService implements IBaseService
{

    public function __construct(private MpmPlugin $mpmPlugin)
    {
    }

    public function getById($id)
    {
        return $this->mpmPlugin->newQuery()->findOrFail($id);
    }

    public function create($data)
    {
        // TODO: Implement create() method.
    }

    public function update($model, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }

    public function getAll($limit = null)
    {
        if ($limit) {
            return $this->mpmPlugin->newQuery()
                ->paginate($limit);
        }
        return $this->mpmPlugin->newQuery()
            ->get();
    }
}