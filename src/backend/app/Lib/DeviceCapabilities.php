<?php

declare(strict_types=1);

namespace App\Lib;

use App\Enums\DeviceTokenUnit;

/**
 * What a device's manufacturer integration lets MPM do out of band, see
 * {@see \App\Services\DeviceControlService::capabilities()}.
 */
class DeviceCapabilities {
    public function __construct(
        public readonly bool $tokenGeneration,
        public readonly ?DeviceTokenUnit $creditUnit = null,
        public readonly ?string $tokenGenerationBlockedReason = null,
    ) {}
}
