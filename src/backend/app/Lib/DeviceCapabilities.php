<?php

declare(strict_types=1);

namespace App\Lib;

use App\Enums\DeviceTokenUnit;
use App\Enums\VendingCapability;

/**
 * What a device's manufacturer integration lets MPM do out of band, see
 * {@see \App\Services\DeviceControlService::capabilities()}.
 */
class DeviceCapabilities {
    /**
     * @param list<VendingCapability> $tokenTypes
     */
    public function __construct(
        public readonly array $tokenTypes,
        public readonly ?DeviceTokenUnit $creditUnit = null,
        public readonly ?string $tokenGenerationBlockedReason = null,
    ) {}
}
