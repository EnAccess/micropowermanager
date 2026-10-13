<?php

namespace App\Plugins\OpenPaygo\Services;

use App\Models\Manufacturer;
use InvalidArgumentException;

class ManufacturerService {
    public function __construct(private Manufacturer $manufacturer) {}

    public function register(string $name, string $type): void {
        if (trim($name) === '' || !in_array($type, ['shs', 'e-bike'], true)) {
            throw new InvalidArgumentException('A manufacturer name and time-based device type (shs or e-bike) are required.');
        }

        $manufacturer = $this->manufacturer->newQuery()
            ->where('name', $name)
            ->where('type', $type)
            ->first();

        if ($manufacturer === null) {
            $this->manufacturer->newQuery()->create([
                'name' => $name,
                'type' => $type,
                'api_name' => 'OpenPaygoApi',
            ]);
        } elseif ($manufacturer->api_name !== null && $manufacturer->api_name !== 'OpenPaygoApi') {
            throw new InvalidArgumentException('This manufacturer already uses a different API integration.');
        } elseif ($manufacturer->api_name === null) {
            $manufacturer->api_name = 'OpenPaygoApi';
            $manufacturer->save();
        }
    }
}
