<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthRouteTest extends TestCase {
    /**
     * A basic feature test for the Laravel Health route.
     */
    public function testHealthRouteIsUp(): void {
        $response = $this->get('/up');

        $response->assertStatus(200);
    }
}
