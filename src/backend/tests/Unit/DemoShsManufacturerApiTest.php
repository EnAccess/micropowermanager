<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Device;
use App\Plugins\DemoShsManufacturer\DemoShsManufacturerApi;
use App\Plugins\DemoShsManufacturer\Models\DemoShsTransaction;
use Tests\TestCase;

class DemoShsManufacturerApiTest extends TestCase {
    public function testItReportsDeviceAsMapped(): void {
        $info = $this->api()->getDeviceInfo($this->device('996995411'));

        $this->assertTrue($info['mapped']);
        $this->assertSame('996995411', $info['device']['serial']);
        $this->assertSame('Demo SHS Unit', $info['device']['model']);
    }

    public function testItReportsSerialEndingInZeroAsNotMapped(): void {
        $info = $this->api()->getDeviceInfo($this->device('996995410'));

        $this->assertFalse($info['mapped']);
        $this->assertNull($info['device']);
    }

    private function api(): DemoShsManufacturerApi {
        return new DemoShsManufacturerApi(new DemoShsTransaction());
    }

    private function device(string $serial): Device {
        $device = new Device();
        $device->device_serial = $serial;

        return $device;
    }
}
