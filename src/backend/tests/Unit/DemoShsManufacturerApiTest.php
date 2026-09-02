<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Device;
use App\Models\Token;
use App\Plugins\DemoShsManufacturer\DemoShsManufacturerApi;
use App\Plugins\DemoShsManufacturer\Models\DemoShsTransaction;
use Illuminate\Database\Eloquent\Builder;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tests\TestCase;

class DemoShsManufacturerApiTest extends TestCase {
    use MockeryPHPUnitIntegration;

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

    public function testItResetsADeviceWithACreditlessToken(): void {
        $result = $this->api()->clearDevice($this->device('996995411'));

        $this->assertNotEmpty($result['token']);
        $this->assertSame(Token::TYPE_RESET, $result['token_type']);
        $this->assertNull($result['token_unit']);
        $this->assertNull($result['token_amount']);
    }

    private function api(): DemoShsManufacturerApi {
        return new DemoShsManufacturerApi($this->demoShsTransaction());
    }

    private function demoShsTransaction(): DemoShsTransaction {
        $manufacturerTransaction = new \stdClass();
        $manufacturerTransaction->id = 1;

        $builder = \Mockery::mock(Builder::class);
        $builder->shouldReceive('create')->with([])->andReturn($manufacturerTransaction);

        /** @var DemoShsTransaction&MockInterface $demoShsTransaction */
        $demoShsTransaction = \Mockery::mock(DemoShsTransaction::class);
        $demoShsTransaction->shouldReceive('newQuery')->andReturn($builder);

        return $demoShsTransaction;
    }

    private function device(string $serial): Device {
        $device = new Device();
        $device->device_serial = $serial;

        return $device;
    }
}
