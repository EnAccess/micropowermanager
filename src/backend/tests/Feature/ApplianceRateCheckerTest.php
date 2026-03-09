<?php

namespace Tests\Feature;

use App\Models\ApplianceRate;
use App\Models\SmsApplianceRemindRate;
use App\Models\Ticket\Ticket;
use App\Services\SmsService;
use Database\Factories\ApplianceFactory;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceRateFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\MainSettingsFactory;
use Database\Factories\SmsApplianceRemindRateFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class ApplianceRateCheckerTest extends TestCase {
    use CreateEnvironments;

    private function setUpApplianceWithDueRate(int $dueDaysFromNow = 0, bool $reminderEnabled = true): ApplianceRate {
        $this->createTestData();
        $this->createCluster(1);
        $this->createMiniGrid(1);
        $this->createCity(1);
        $this->createPerson();

        MainSettingsFactory::new()->create();

        $applianceType = ApplianceTypeFactory::new()->create();
        $appliance = ApplianceFactory::new()->create([
            'appliance_type_id' => $applianceType->id,
        ]);
        $appliancePerson = AppliancePersonFactory::new()->create([
            'person_id' => $this->person->id,
            'appliance_id' => $appliance->id,
            'total_cost' => 100000,
            'rate_count' => 10,
            'creator_type' => 'user',
            'creator_id' => $this->user->id,
        ]);

        SmsApplianceRemindRateFactory::new()->create([
            'appliance_id' => $appliance->id,
            'remind_rate' => 7,
            'overdue_remind_rate' => 14,
            'enabled' => $reminderEnabled,
        ]);

        return ApplianceRateFactory::new()->create([
            'appliance_person_id' => $appliancePerson->id,
            'rate_cost' => 10000,
            'remaining' => 10000,
            'due_date' => now()->addDays($dueDaysFromNow)->toDateString(),
            'remind' => 0,
        ]);
    }

    public function testCommandSkipsDisabledReminders(): void {
        $this->setUpApplianceWithDueRate(dueDaysFromNow: 0, reminderEnabled: false);

        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldNotReceive('sendSms');
        });

        $this->artisan('appliance-rate:check', ['--company-id' => 1])
            ->assertSuccessful();

        $this->assertEquals(0, Ticket::query()->count());
    }

    public function testCommandProcessesEnabledReminders(): void {
        $this->setUpApplianceWithDueRate(dueDaysFromNow: -3, reminderEnabled: true);

        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('sendSms')->once();
        });

        $this->artisan('appliance-rate:check', ['--company-id' => 1])
            ->assertSuccessful();

        $this->assertEquals(1, Ticket::query()->count());
    }

    public function testCommandCreatesTicketWithCorrectOwner(): void {
        $applianceRate = $this->setUpApplianceWithDueRate(dueDaysFromNow: -3, reminderEnabled: true);

        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('sendSms');
        });

        $this->artisan('appliance-rate:check', ['--company-id' => 1])
            ->assertSuccessful();

        $ticket = Ticket::query()->first();
        $this->assertNotNull($ticket);
        $this->assertEquals('person', $ticket->owner_type);
        $this->assertEquals($this->person->id, $ticket->owner_id);
    }

    public function testCommandDoesNotProcessFutureRatesOutsideWindow(): void {
        $this->setUpApplianceWithDueRate(dueDaysFromNow: 30, reminderEnabled: true);

        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldNotReceive('sendSms');
        });

        $this->artisan('appliance-rate:check', ['--company-id' => 1])
            ->assertSuccessful();

        $this->assertEquals(0, Ticket::query()->count());
    }

    public function testEnabledFilterOnlyReturnsEnabledRates(): void {
        $this->createTestData();

        $applianceType = ApplianceTypeFactory::new()->create();
        $enabledAppliance = ApplianceFactory::new()->create(['appliance_type_id' => $applianceType->id]);
        $disabledAppliance = ApplianceFactory::new()->create(['appliance_type_id' => $applianceType->id]);

        SmsApplianceRemindRateFactory::new()->create([
            'appliance_id' => $enabledAppliance->id,
            'enabled' => true,
        ]);
        SmsApplianceRemindRateFactory::new()->create([
            'appliance_id' => $disabledAppliance->id,
            'enabled' => false,
        ]);

        $enabledRates = SmsApplianceRemindRate::query()->where('enabled', true)->get();
        $allRates = SmsApplianceRemindRate::query()->get();

        $this->assertEquals(1, $enabledRates->count());
        $this->assertEquals(2, $allRates->count());
    }
}
