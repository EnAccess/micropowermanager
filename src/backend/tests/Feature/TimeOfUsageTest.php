<?php

namespace Tests\Feature;

use App\Models\TimeOfUsage;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TimeOfUsageTest extends TestCase {
    use CreateEnvironments;

    public function testUserDeletesTimeOfUsageOfTariff() {
        $this->createTestData();
        $meterTariffCount = 5;
        $withTimeOfUsage = true;
        $this->createMeterTariff($meterTariffCount, $withTimeOfUsage);
        $timeOfUsage = TimeOfUsage::query()->first();
        $response = $this->actingAs($this->user)->delete(sprintf('/api/time-of-usages/%s', $timeOfUsage->id));
        $response->assertStatus(200);
    }

    public function actingAs($user, $driver = null) {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
