<?php

namespace App\Services;

use App\Models\Plugins;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PluginsService {
    public function __construct(
        private Plugins $plugin,
    ) {}

    public function create(array $pluginData): Plugins {
        /** @var Plugins $plugin */
        $plugin = $this->plugin->newQuery()->create($pluginData);

        return $plugin;
    }

    public function update($model, array $data): Plugins {
        $model->update($data);
        $model->fresh();

        return $model;
    }

    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit) {
            return $this->plugin->newQuery()->paginate($limit);
        }

        return $this->plugin->newQuery()->get();
    }

    public function getByMpmPluginId($mpmPluginId) {
        return $this->plugin->newQuery()->where('mpm_plugin_id', $mpmPluginId)->first();
    }

    public function isPluginActive(int $pluginId): bool {
        return $this->plugin->newQuery()
            ->where('mpm_plugin_id', '=', $pluginId)
            ->exists();
    }

    public function addPlugin(string $name, string $composerName, string $description): Plugins {
        $pluginData = [
            'name' => $name,
            'composer_name' => $composerName,
            'description' => $description,
        ];

        return $this->create($pluginData);
    }
}
