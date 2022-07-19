<?php

namespace App\Services;

use App\Models\Plugins;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PluginsService implements IBaseService
{
    public function __construct(private Plugins $plugin)
    {
    }

    public function getById($id)
    {
        // TODO: Implement getById() method.
    }

    public function create($pluginData)
    {
       return $this->plugin->newQuery()->create($pluginData);
    }

    public function update($model, $data)
    {
        $model->update($data);
        $model->fresh();

        return $model;
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }

    public function getAll($limit = null)
    {
        if ($limit) {
            return $this->plugin->newQuery()->paginate($limit);
        }
        return $this->plugin->newQuery()->get();
    }

    public function getByMpmPluginId($mpmPluginId)
    {
        return $this->plugin->newQuery()->where('mpm_plugin_id', $mpmPluginId)->first();
    }
}
