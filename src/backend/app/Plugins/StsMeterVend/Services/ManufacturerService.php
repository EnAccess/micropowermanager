<?php

namespace App\Plugins\StsMeterVend\Services;

use App\Models\Manufacturer;

/**
 * Native STS vends meters it did not make, so a tenant may run it without the
 * OEM's cloud plugin. Installing ensures each supported OEM's manufacturer row
 * exists, keyed by the same api_name the OEM's own plugin registers, so the two
 * never duplicate a manufacturer.
 */
class ManufacturerService {
    /** @var array<string, array{name: string, website: string}> */
    public const array SUPPORTED_MANUFACTURERS = [
        'CalinMeterApi' => ['name' => 'Calin Meters', 'website' => 'http://www.calinmeter.com/'],
        'CalinSmartMeterApi' => ['name' => 'Calin Smart Meters', 'website' => 'https://ami.calinhost.com/'],
    ];

    public function __construct(private Manufacturer $manufacturer) {}

    public function register(): void {
        foreach (self::SUPPORTED_MANUFACTURERS as $apiName => $attributes) {
            $this->manufacturer->newQuery()->firstOrCreate(['api_name' => $apiName], $attributes);
        }
    }
}
