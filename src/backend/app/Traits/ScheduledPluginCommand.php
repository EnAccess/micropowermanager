<?php

namespace App\Traits;

use App\Enums\MpmPlugin;
use App\Services\PluginsService;

trait ScheduledPluginCommand {
    protected function checkForPluginStatusIsActive(MpmPlugin $mpmPlugin): bool {
        return resolve(PluginsService::class)->isPluginActive($mpmPlugin);
    }
}
