<?php

namespace App\Services\ImportServices;

final readonly class DeviceImportItem {
    /**
     * @param array<string, mixed>|null $geoJson
     */
    public function __construct(
        public ?string $customer,
        public DeviceInfoItem $deviceInfo,
        public ?array $geoJson,
    ) {}
}
