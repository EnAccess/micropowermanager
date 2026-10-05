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

    private function createApplianceWithRemindRate(bool $upcomingReminderEnabled = false): array {
        $applianceType = ApplianceTypeFactory::new()->create();
        $appliance = ApplianceFactory::new()->create([
            'appliance_type_id' => $applianceType->id,
        ]);
        $remindRate = SmsApplianceRemindRateFactory::new()->create([
            'appliance_id' => $appliance->id,
            'remind_rate' => 7,
            'overdue_remind_rate' => 14,
            'upcoming_reminder_enabled' => $upcomingReminderEnabled,
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
            'upcoming_reminder_enabled' => true,
            'overdue_reminder_enabled' => false,
        ];

        $response = $this->actingAs($this->user)->post('/api/sms-appliance-remind-rate', $postData);
        $response->assertStatus(200);

        $remindRate = SmsApplianceRemindRate::query()->where('appliance_id', $appliance->id)->first();
        $this->assertNotNull($remindRate);
        $this->assertEquals(7, $remindRate->remind_rate);
        $this->assertEquals(14, $remindRate->overdue_remind_rate);
        $this->assertTrue($remindRate->upcoming_reminder_enabled);
        $this->assertFalse($remindRate->overdue_reminder_enabled);
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
        $this->assertFalse($remindRate->upcoming_reminder_enabled);
        $this->assertFalse($remindRate->overdue_reminder_enabled);
    }

    public function testUserUpdatesApplianceRemindRate(): void {
        $this->createTestData();
        [$appliance, $remindRate] = $this->createApplianceWithRemindRate(false);

        $putData = [
            'overdue_remind_rate' => 21,
            'remind_rate' => 10,
            'overdue_reminder_enabled' => true,
        ];

        $response = $this->actingAs($this->user)->put(
            sprintf('/api/sms-appliance-remind-rate/%s', $remindRate->id),
            $putData
        );
        $response->assertStatus(200);

        $remindRate->refresh();
        $this->assertEquals(10, $remindRate->remind_rate);
        $this->assertEquals(21, $remindRate->overdue_remind_rate);
        $this->assertTrue($remindRate->overdue_reminder_enabled);
    }

    public function testUserTogglesUpcomingReminder(): void {
        $this->createTestData();
        [$appliance, $remindRate] = $this->createApplianceWithRemindRate(false);

        $this->assertFalse($remindRate->upcoming_reminder_enabled);

        $putData = [
            'overdue_remind_rate' => $remindRate->overdue_remind_rate,
            'remind_rate' => $remindRate->remind_rate,
            'upcoming_reminder_enabled' => true,
        ];

        $response = $this->actingAs($this->user)->put(
            sprintf('/api/sms-appliance-remind-rate/%s', $remindRate->id),
            $putData
        );
        $response->assertStatus(200);

        $remindRate->refresh();
        $this->assertTrue($remindRate->upcoming_reminder_enabled);
    }

    public function testUserCannotSetNegativeReminderDays(): void {
        $this->createTestData();
        [$appliance, $remindRate] = $this->createApplianceWithRemindRate();

        $response = $this->actingAs($this->user)->putJson(
            sprintf('/api/sms-appliance-remind-rate/%s', $remindRate->id),
            [
                'overdue_remind_rate' => 14,
                'remind_rate' => -1,
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['remind_rate']);
    }

    public function testUserCannotSetOverdueReminderOnDueDate(): void {
        $this->createTestData();
        [$appliance, $remindRate] = $this->createApplianceWithRemindRate();

        $response = $this->actingAs($this->user)->putJson(
            sprintf('/api/sms-appliance-remind-rate/%s', $remindRate->id),
            [
                'overdue_remind_rate' => 0,
                'remind_rate' => 7,
                'overdue_reminder_enabled' => true,
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['overdue_remind_rate' => 'days after due date']);
    }

    public function testUserCanSaveZeroOverdueDaysWhenOverdueReminderDisabled(): void {
        $this->createTestData();
        [$appliance, $remindRate] = $this->createApplianceWithRemindRate();

        $response = $this->actingAs($this->user)->putJson(
            sprintf('/api/sms-appliance-remind-rate/%s', $remindRate->id),
            [
                'overdue_remind_rate' => 0,
                'remind_rate' => 7,
                'upcoming_reminder_enabled' => true,
                'overdue_reminder_enabled' => false,
            ]
        );

        $response->assertStatus(200);
        $remindRate->refresh();
        $this->assertEquals(0, $remindRate->overdue_remind_rate);
        $this->assertFalse($remindRate->overdue_reminder_enabled);
    }
}
