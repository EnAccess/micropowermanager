<?php

namespace App\Traits;

use App\Models\Plugins;

// FIXME: `ScheduledPluginCommand` is used in `packages` with is currently
// not covered by Larastan.
// @phpstan-ignore trait.unused
trait ScheduledPluginCommand {
    private static int $ACTIVE = 1;

    protected function checkForPluginStatusIsActive($mpmPluginId): bool {
        $plugin = Plugins::query()->where('mpm_plugin_id', $mpmPluginId)->first();

        return $plugin && $plugin->status === self::$ACTIVE;
    }
}
