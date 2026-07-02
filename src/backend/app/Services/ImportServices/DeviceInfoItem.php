<?php

namespace App\Services\ImportServices;

final readonly class DeviceInfoItem {
    public function __construct(
        public string $serialNumber,
        public string $type,
        public ?string $manufacturer,
        public ?MeterTypeItem $meterType,
        public ?string $connectionType,
        public ?string $connectionGroup,
        public ?string $tariff,
        public ?string $appliance,
    ) {}
}
