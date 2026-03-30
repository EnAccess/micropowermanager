<?php

namespace App\Services;

use App\Models\MpmPlugin;
use App\Models\Plugins;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class PluginsService {
    public function __construct(
        private Plugins $plugin,
        private MpmPluginService $mpmPluginService,
        private RegistrationTailService $registrationTailService,
    ) {}

    /**
     * @param array<string, mixed> $pluginData
     */
    public function create(array $pluginData): Plugins {
        return $this->plugin->newQuery()->create($pluginData);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Plugins $model, array $data): Plugins {
        $model->update($data);
        $model->fresh();

        return $model;
    }

    /**
     * @return Collection<int, Plugins>|LengthAwarePaginator<int, Plugins>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit) {
            return $this->plugin->newQuery()->paginate($limit);
        }

        return $this->plugin->newQuery()->get();
    }

    public function getByMpmPluginId(int $mpmPluginId): ?Plugins {
        return $this->plugin->newQuery()
            ->where('mpm_plugin_id', $mpmPluginId)
            ->first();
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

    /**
     * Enable a plugin by creating DB entry, adding registration tail, and running installation command.
     *
     * @throws \Exception
     */
    public function enablePlugin(int $mpmPluginId): Plugins {
        // Get the MpmPlugin from central database
        $mpmPlugin = $this->mpmPluginService->getById($mpmPluginId);
        if (!$mpmPlugin instanceof MpmPlugin) {
            throw new \Exception("Plugin with ID {$mpmPluginId} not found");
        }

        // 1. Create the plugin DB entry
        $pluginData = [
            'mpm_plugin_id' => $mpmPluginId,
            'status' => 1,
        ];
        $plugin = $this->create($pluginData);

        // 2. Add registration tail (if exists)
        $registrationTail = $this->registrationTailService->getFirst();
        $this->registrationTailService->addMpmPluginToRegistrationTail($registrationTail, $mpmPlugin);

        // 3. Run installation command
        Artisan::call($mpmPlugin->installation_command);

        return $plugin;
    }

    public function setupDemoManufacturerPlugins(): void {
        // Enable demo manufacturer plugins by default
        $demoPlugins = [
            MpmPlugin::DEMO_METER_MANUFACTURER,
            MpmPlugin::DEMO_SHS_MANUFACTURER,
        ];

        foreach ($demoPlugins as $pluginId) {
            try {
                $this->enablePlugin($pluginId);
            } catch (\Exception $e) {
                // Plugin might not be available, continue with others
                Log::info("Demo plugin {$pluginId} not available: ".$e->getMessage());
            }
        }
    }
}
