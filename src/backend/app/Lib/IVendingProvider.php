<?php

declare(strict_types=1);

namespace App\Lib;

use App\Models\Meter\Meter;

/**
 * When a vending provider's plugin is disabled, its meters fall back to their
 * manufacturer's API only if the provider holds nothing for them. A provider
 * that already vended a meter has state the manufacturer cannot know about, so
 * handing the meter back would issue tokens the meter may reject or double count.
 */
interface IVendingProvider extends IManufacturerAPI {
    public function isConfiguredFor(Meter $meter): bool;
}
