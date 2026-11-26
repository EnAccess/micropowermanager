<?php

namespace App\Services;

use App\Models\Plugins;
use App\Plugins\DemoMeterManufacturer\Providers\DemoMeterManufacturerServiceProvider;
use App\Plugins\DemoShsManufacturer\Providers\DemoShsManufacturerServiceProvider;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class PluginsService {
    public function __construct(
        private Plugins $plugin,
        private RegistrationTailService $registrationTailService,
        private MpmPluginCatalog $mpmPluginCatalog,
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

    public function getByMpmPlugin(string $mpmPlugin): ?Plugins {
        return $this->plugin->newQuery()
            ->where('mpm_plugin', $mpmPlugin)
            ->first();
    }

    public function isPluginActive(string $mpmPlugin): bool {
        return $this->plugin->newQuery()
            ->where('mpm_plugin', $mpmPlugin)
            ->where('status', Plugins::ACTIVE)
            ->exists();
    }

    /**
     * @return list<string>
     */
    public function getActiveMpmPlugins(): array {
        return $this->plugin->newQuery()
            ->where('status', Plugins::ACTIVE)
            ->pluck('mpm_plugin')
            ->all();
    }

    /**
     * Enable a plugin by creating DB entry, adding registration tail, and running installation command.
     *
     * @throws \Exception
     */
    public function enablePlugin(string $mpmPlugin): Plugins {
        $plugin = $this->create([
            'mpm_plugin' => $mpmPlugin,
            'status' => Plugins::ACTIVE,
        ]);
        $this->registrationTailService->addMpmPluginToRegistrationTail($mpmPlugin);
        Artisan::call($this->mpmPluginCatalog->installationCommand($mpmPlugin));

        return $plugin;
    }

    public function setupDemoManufacturerPlugins(): void {
        foreach ([DemoMeterManufacturerServiceProvider::SLUG, DemoShsManufacturerServiceProvider::SLUG] as $demoPlugin) {
            try {
                $this->enablePlugin($demoPlugin);
            } catch (\Exception $e) {
                Log::info("Demo plugin {$demoPlugin} not available: ".$e->getMessage());
            }
        }
    }
}
