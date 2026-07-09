<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Device;
use App\Plugins\SparkShs\Exceptions\SparkShsApiResponseException;
use App\Plugins\SparkShs\Http\Clients\SparkShsApiClient;
use App\Plugins\SparkShs\Models\SparkShsTransaction;
use App\Plugins\SparkShs\Modules\Api\SparkShsApi;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\Response;
use Tests\TestCase;

class SparkShsApiDeviceInfoTest extends TestCase {
    public function testItReportsDeviceAsMappedWithCuratedFields(): void {
        $kit = ['serial' => '123456', 'token' => 'abc', 'n' => 3, 'type' => 'days', 'devices' => [null]];
        $info = $this->apiReturning(new GuzzleResponse(200, [], (string) json_encode($kit)))
            ->getDeviceInfo($this->device('123456'));

        $this->assertTrue($info['mapped']);
        $this->assertSame(['serial' => '123456', 'type' => 'days'], $info['device']);
    }

    public function testItReportsForbiddenSerialAsNotMapped(): void {
        $info = $this->apiReturning(new GuzzleResponse(403, [], (string) json_encode(['message' => 'Forbidden'])))
            ->getDeviceInfo($this->device('123456'));

        $this->assertFalse($info['mapped']);
        $this->assertNull($info['device']);
    }

    public function testItThrowsOnAuthError(): void {
        $this->expectException(SparkShsApiResponseException::class);

        $this->apiReturning(new GuzzleResponse(401, [], (string) json_encode(['message' => 'Unauthorized'])))
            ->getDeviceInfo($this->device('123456'));
    }

    public function testItThrowsOnServerError(): void {
        $this->expectException(SparkShsApiResponseException::class);

        $this->apiReturning(new GuzzleResponse(500, [], (string) json_encode(['message' => 'boom'])))
            ->getDeviceInfo($this->device('123456'));
    }

    private function apiReturning(GuzzleResponse $response): SparkShsApi {
        $client = \Mockery::mock(SparkShsApiClient::class);
        $client->shouldReceive('get')
            ->with('products/kits/123456')
            ->andReturn(new Response($response));

        return new SparkShsApi(new SparkShsTransaction(), $client);
    }

    private function device(string $serial): Device {
        $device = new Device();
        $device->device_serial = $serial;

        return $device;
    }
}
