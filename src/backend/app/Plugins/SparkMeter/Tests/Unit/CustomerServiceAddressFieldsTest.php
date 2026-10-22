<?php

namespace App\Plugins\SparkMeter\Tests\Unit;

use App\Plugins\SparkMeter\Models\SmSite;
use App\Plugins\SparkMeter\Services\CustomerService;
use Database\Factories\Address\AddressFactory;
use Database\Factories\CityFactory;
use Database\Factories\CountryFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class CustomerServiceAddressFieldsTest extends TestCase {
    public function testAddressComponentFieldsMapsMpmAddressToSparkComponentFields(): void {
        $country = CountryFactory::new()->make(['country_name' => 'Testland']);
        $city = CityFactory::new()->make(['name' => 'Testville']);
        $address = AddressFactory::new()->make(['street' => '123 Test St']);
        $address->setRelation('city', $city->setRelation('country', $country));

        $fields = CustomerService::addressComponentFields($address);

        $this->assertSame([
            'street1' => '123 Test St',
            'street2' => '',
            'city' => 'Testville',
            'state' => '',
            'postalcode' => '',
            'country' => 'Testland',
        ], $fields);
    }

    public function testAddressComponentFieldsToleratesANullAddress(): void {
        $fields = CustomerService::addressComponentFields(null);

        $this->assertSame([
            'street1' => '',
            'street2' => '',
            'city' => '',
            'state' => '',
            'postalcode' => '',
            'country' => '',
        ], $fields);
    }

    public function testUpdateSparkCustomerInfoSendsComponentAddressFieldsNotTheDeprecatedAddressField(): void {
        SmSite::query()->create([
            'site_id' => 'site-1',
            'mpm_mini_grid_id' => 1,
            'thundercloud_url' => 'https://site-1.sparkmeter.cloud/api/v0',
            'thundercloud_token' => 'token-1',
            'is_authenticated' => 1,
            'is_online' => 1,
        ]);

        $capturedRequests = [];
        $history = Middleware::history($capturedRequests);
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, [], (string) json_encode(['customer_id' => 'cust-1', 'error' => null])),
        ]));
        $stack->push($history);
        $this->app->instance(Client::class, new Client(['handler' => $stack]));

        /** @var CustomerService $customerService */
        $customerService = $this->app->make(CustomerService::class);

        $customerService->updateSparkCustomerInfo([
            'id' => 'cust-1',
            'active' => true,
            'meter_tariff_name' => 'Pre-paid Tariff',
            'name' => 'Test Customer',
            'coords' => '1.0,2.0',
            'phone_number' => '+15555550100',
            'address_fields' => [
                'street1' => '123 Test St',
                'street2' => '',
                'city' => 'Testville',
                'state' => '',
                'postalcode' => '',
                'country' => 'Testland',
            ],
        ], 'site-1');

        $this->assertCount(1, $capturedRequests);
        $body = json_decode((string) $capturedRequests[0]['request']->getBody(), true);

        $this->assertArrayNotHasKey('address', $body);
        $this->assertSame('123 Test St', $body['street1']);
        $this->assertSame('Testville', $body['city']);
        $this->assertSame('Testland', $body['country']);
        $this->assertSame('', $body['street2']);
        $this->assertSame('', $body['state']);
        $this->assertSame('', $body['postalcode']);
    }
}
