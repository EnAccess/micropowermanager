<?php

namespace App\Lib;

/**
 * Outcome of asking a manufacturer's device management API whether a device
 * is still mapped on the manufacturer side, see
 * {@see \App\Services\DeviceService::verifyManufacturerMapping()}.
 */
class DeviceMappingResult {
    /**
     * @param array<string, mixed>|null $device manufacturer-specific device details
     */
    public function __construct(
        public readonly bool $supported,
        public readonly bool $mapped = false,
        public readonly ?array $device = null,
    ) {}
}
