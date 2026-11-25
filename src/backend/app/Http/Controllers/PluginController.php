<?php

namespace App\Http\Controllers;

use App\Enums\MpmPlugin;
use App\Http\Resources\ApiResource;
use App\Models\Plugins;
use App\Services\PluginsService;
use App\Services\RegistrationTailService;
use Illuminate\Http\Request;

class PluginController extends Controller {
    public function __construct(
        private PluginsService $pluginsService,
        private RegistrationTailService $registrationTailService,
    ) {}

    public function index(Request $request): ApiResource {
        return ApiResource::make($this->pluginsService->getAll());
    }

    public function update(Request $request, MpmPlugin $mpmPlugin): ApiResource {
        $plugin = $this->pluginsService->getByMpmPlugin($mpmPlugin);

        if (!$plugin && !$request->input('checked')) {
            throw new \Exception('Plugin not found');
        }

        if ($request->input('checked')) {
            // Check if this is the first time we are installing the plugin.
            // In that case we also need to run install commands, if present
            if (!$plugin instanceof Plugins) {
                return ApiResource::make($this->pluginsService->enablePlugin($mpmPlugin));
            }

            $updatedPlugin = $this->pluginsService->update($plugin, ['status' => Plugins::ACTIVE]);
            $this->registrationTailService->addMpmPluginToRegistrationTail($mpmPlugin);
        } else {
            $updatedPlugin = $this->pluginsService->update($plugin, ['status' => Plugins::INACTIVE]);
            $this->registrationTailService->removeMpmPluginFromRegistrationTail($mpmPlugin);
        }

        return ApiResource::make($updatedPlugin);
    }
}
