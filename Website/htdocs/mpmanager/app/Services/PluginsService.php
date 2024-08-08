<?php

namespace App\Services;

use App\Models\Plugins;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<Plugins>
 */
class PluginsService implements IBaseService
{
    public function __construct(
        private Plugins $plugin
    ) {
    }

    public function getById(int $id): Plugins
    {
        throw new \Exception('Method getById() not yet implemented.');
    }

    public function create(array $pluginData): Plugins
    {
        return $this->plugin->newQuery()->create($pluginData);
    }

    public function update($model, array $data): Plugins
    {
        $model->update($data);
        $model->fresh();

        return $model;
    }

    public function delete($model): ?bool
    {
        throw new \Exception('Method delete() not yet implemented.');
    }

    public function getAll(?int $limit = null): Collection|LengthAwarePaginator
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
