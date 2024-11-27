<?php

namespace App\Traits;

use App\Models\Plugins;

trait ScheduledPluginCommand {
    private static int $ACTIVE = 1;

    protected function checkForPluginStatusIsActive($mpmPluginId) {
        $plugin = Plugins::query()->where('mpm_plugin_id', $mpmPluginId)->first();

        return $plugin && $plugin->status === self::$ACTIVE;
    }
}
