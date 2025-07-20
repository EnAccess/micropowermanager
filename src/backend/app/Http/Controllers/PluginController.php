<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\MpmPluginService;
use App\Services\PluginsService;
use App\Services\RegistrationTailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class PluginController extends Controller {
    public function __construct(
        private PluginsService $pluginsService,
        private MpmPluginService $mpmPluginService,
        private RegistrationTailService $registrationTailService,
    ) {}

    public function index(Request $request): ApiResource {
        return ApiResource::make($this->pluginsService->getAll());
    }

    public function update(Request $request, int $mpmPluginId): ApiResource {
        $plugin = $this->pluginsService->getByMpmPluginId($mpmPluginId);
        $mpmPlugin = $this->mpmPluginService->getById($mpmPluginId);
        $registrationTail = $this->registrationTailService->getFirst();

        $pluginData = [
            'mpm_plugin_id' => $mpmPluginId,
            'status' => $request->input('checked'),
        ];

        if (!$plugin && !$request->input('checked')) {
            throw new \Exception('Plugin not found');
        }

        if ($request->input('checked')) {
            // Check if this is the first time we are installing the plugin.
            // In that case we also need to run install commands, if present
            if (!$plugin) {
                $createdPlugin = $this->pluginsService->create($pluginData);
                $this->registrationTailService->addMpmPluginToRegistrationTail($registrationTail, $mpmPlugin);

                Artisan::call($mpmPlugin->installation_command);

                return ApiResource::make($createdPlugin);
            }

            $updatedPlugin = $this->pluginsService->update($plugin, $pluginData);

            $this->registrationTailService->addMpmPluginToRegistrationTail($registrationTail, $mpmPlugin);
        } else {
            $updatedPlugin = $this->pluginsService->update($plugin, $pluginData);

            $this->registrationTailService->removeMpmPluginFromRegistrationTail($registrationTail, $mpmPlugin);
        }

        return ApiResource::make($updatedPlugin);
    }
}
