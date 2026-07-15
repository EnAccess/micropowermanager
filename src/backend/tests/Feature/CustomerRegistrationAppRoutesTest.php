<?php

namespace Tests\Feature;

use Tests\CreateEnvironments;
use Tests\TestCase;

class CustomerRegistrationAppRoutesTest extends TestCase {
    use CreateEnvironments;

    public function testAllListEndpointsRespond(): void {
        $this->createTestData();

        $endpoints = [
            '/api/customer-registration-app/people',
            '/api/customer-registration-app/manufacturers',
            '/api/customer-registration-app/meter-types',
            '/api/customer-registration-app/tariffs',
            '/api/customer-registration-app/cities',
            '/api/customer-registration-app/connection-groups',
            '/api/customer-registration-app/connection-types',
            '/api/customer-registration-app/sub-connection-types',
        ];

        foreach ($endpoints as $endpoint) {
            $this->actingAs($this->user)->getJson($endpoint)->assertStatus(200);
        }
    }
}
