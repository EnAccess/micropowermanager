<?php

namespace App\Traits;

use App\Services\PluginsService;

trait ScheduledPluginCommand {
    protected function checkForPluginStatusIsActive(string $mpmPlugin): bool {
        return resolve(PluginsService::class)->isPluginActive($mpmPlugin);
    }
}
