<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Device;
use App\Plugins\SunKingSHS\Http\Clients\SunKingSHSApiClient;
use App\Plugins\SunKingSHS\Models\SunKingCredential;
use App\Plugins\SunKingSHS\Models\SunKingTransaction;
use App\Plugins\SunKingSHS\Modules\Api\SunKingSHSApi;
use App\Plugins\SunKingSHS\Services\SunKingCredentialService;
use Tests\TestCase;

class SunKingSHSApiDeviceInfoTest extends TestCase {
    public function testItReturnsCuratedFieldsWhenMapped(): void {
        $info = $this->apiReturning(['device' => [
            'code' => '312',
            'name' => 'SK-312 Pro EasyBuy',
            'keypad_type' => 2,
            'is_paygo' => true,
            'is_gsm' => false,
            'version' => '2',
        ]])->getDeviceInfo($this->device('996995411'));

        $this->assertTrue($info['mapped']);
        $this->assertSame([
            'code' => '312',
            'name' => 'SK-312 Pro EasyBuy',
            'is_paygo' => true,
            'is_gsm' => false,
            'version' => '2',
        ], $info['device']);
    }

    public function testItReportsNotMappedWhenDeviceMissing(): void {
        $info = $this->apiReturning(null)->getDeviceInfo($this->device('996995411'));

        $this->assertFalse($info['mapped']);
        $this->assertNull($info['device']);
    }

    /**
     * @param array<string, mixed>|null $getResult
     */
    private function apiReturning(?array $getResult): SunKingSHSApi {
        $credential = new SunKingCredential();

        $credentialService = \Mockery::mock(SunKingCredentialService::class);
        $credentialService->shouldReceive('getCredentials')->andReturn($credential);
        $credentialService->shouldReceive('updateCredentials')->andReturn($credential);

        $client = \Mockery::mock(SunKingSHSApiClient::class);
        $client->shouldReceive('authentication')->andReturn(['access_token' => 'token', 'token_expires_in' => 123]);
        $client->shouldReceive('get')->andReturn($getResult);

        return new SunKingSHSApi($credentialService, new SunKingTransaction(), $client);
    }

    private function device(string $serial): Device {
        $device = new Device();
        $device->device_serial = $serial;

        return $device;
    }
}
