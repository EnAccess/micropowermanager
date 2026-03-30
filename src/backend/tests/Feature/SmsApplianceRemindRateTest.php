<?php

namespace Tests\Feature;

use App\Models\SmsApplianceRemindRate;
use Database\Factories\ApplianceFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\SmsApplianceRemindRateFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class SmsApplianceRemindRateTest extends TestCase {
    use CreateEnvironments;

    private function createApplianceWithRemindRate(bool $enabled = false): array {
        $applianceType = ApplianceTypeFactory::new()->create();
        $appliance = ApplianceFactory::new()->create([
            'appliance_type_id' => $applianceType->id,
        ]);
        $remindRate = SmsApplianceRemindRateFactory::new()->create([
            'appliance_id' => $appliance->id,
            'remind_rate' => 7,
            'overdue_remind_rate' => 14,
            'enabled' => $enabled,
        ]);

        return [$appliance, $remindRate];
    }

    public function testUserGetsApplianceRemindRateList(): void {
        $this->createTestData();
        $this->createApplianceWithRemindRate();

        $response = $this->actingAs($this->user)->get('/api/sms-appliance-remind-rate');
        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response['data']));
    }

    public function testUserCreatesApplianceRemindRate(): void {
        $this->createTestData();
        $applianceType = ApplianceTypeFactory::new()->create();
        $appliance = ApplianceFactory::new()->create([
            'appliance_type_id' => $applianceType->id,
        ]);

        $postData = [
            'appliance_type_id' => $appliance->id,
            'overdue_remind_rate' => 14,
            'remind_rate' => 7,
            'enabled' => true,
        ];

        $response = $this->actingAs($this->user)->post('/api/sms-appliance-remind-rate', $postData);
        $response->assertStatus(200);

        $remindRate = SmsApplianceRemindRate::query()->where('appliance_id', $appliance->id)->first();
        $this->assertNotNull($remindRate);
        $this->assertEquals(7, $remindRate->remind_rate);
        $this->assertEquals(14, $remindRate->overdue_remind_rate);
        $this->assertTrue((bool) $remindRate->enabled);
    }

    public function testUserCreatesApplianceRemindRateDisabledByDefault(): void {
        $this->createTestData();
        $applianceType = ApplianceTypeFactory::new()->create();
        $appliance = ApplianceFactory::new()->create([
            'appliance_type_id' => $applianceType->id,
        ]);

        $postData = [
            'appliance_type_id' => $appliance->id,
            'overdue_remind_rate' => 14,
            'remind_rate' => 7,
        ];

        $response = $this->actingAs($this->user)->post('/api/sms-appliance-remind-rate', $postData);
        $response->assertStatus(200);

        $remindRate = SmsApplianceRemindRate::query()->where('appliance_id', $appliance->id)->first();
        $this->assertNotNull($remindRate);
        $this->assertFalse((bool) $remindRate->enabled);
    }

    public function testUserUpdatesApplianceRemindRate(): void {
        $this->createTestData();
        [$appliance, $remindRate] = $this->createApplianceWithRemindRate(false);

        $putData = [
            'overdue_remind_rate' => 21,
            'remind_rate' => 10,
            'enabled' => true,
        ];

        $response = $this->actingAs($this->user)->put(
            sprintf('/api/sms-appliance-remind-rate/%s', $remindRate->id),
            $putData
        );
        $response->assertStatus(200);

        $remindRate->refresh();
        $this->assertEquals(10, $remindRate->remind_rate);
        $this->assertEquals(21, $remindRate->overdue_remind_rate);
        $this->assertTrue((bool) $remindRate->enabled);
    }

    public function testUserTogglesRemindRateEnabled(): void {
        $this->createTestData();
        [$appliance, $remindRate] = $this->createApplianceWithRemindRate(false);

        $this->assertFalse((bool) $remindRate->enabled);

        $putData = [
            'overdue_remind_rate' => $remindRate->overdue_remind_rate,
            'remind_rate' => $remindRate->remind_rate,
            'enabled' => true,
        ];

        $response = $this->actingAs($this->user)->put(
            sprintf('/api/sms-appliance-remind-rate/%s', $remindRate->id),
            $putData
        );
        $response->assertStatus(200);

        $remindRate->refresh();
        $this->assertTrue((bool) $remindRate->enabled);
    }
}
