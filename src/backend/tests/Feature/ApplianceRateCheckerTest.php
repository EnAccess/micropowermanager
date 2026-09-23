<?php

namespace Tests\Feature;

use App\Models\ApplianceRate;
use App\Models\Ticket\Ticket;
use App\Services\SmsApplianceRemindRateService;
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

    private function setUpCustomer(): void {
        $this->createTestData();
        $this->createCluster(1);
        $this->createMiniGrid(1);
        $this->createCity(1);
        $this->createPerson();

        MainSettingsFactory::new()->create();
    }

    /**
     * @param array<string, mixed> $reminderSettings
     */
    private function createApplianceWithDueRate(
        int $dueDaysFromNow,
        array $reminderSettings,
        int $remaining = 10000,
        int $remind = ApplianceRate::REMIND_NONE,
    ): ApplianceRate {
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
            ...$reminderSettings,
        ]);

        return ApplianceRateFactory::new()->create([
            'appliance_person_id' => $appliancePerson->id,
            'rate_cost' => 10000,
            'remaining' => $remaining,
            'due_date' => now()->addDays($dueDaysFromNow)->toDateString(),
            'remind' => $remind,
        ]);
    }

    private function expectSmsCount(int $count): void {
        $this->mock(SmsService::class, function ($mock) use ($count) {
            if ($count === 0) {
                $mock->shouldNotReceive('sendSms');

                return;
            }
            $mock->shouldReceive('sendSms')->times($count);
        });
    }

    private function runChecker(): void {
        $this->artisan('appliance-rate:check', ['--company-id' => 1])
            ->assertSuccessful();
    }

    public function testCommandSkipsDisabledReminders(): void {
        $this->setUpCustomer();
        $this->createApplianceWithDueRate(3, ['upcoming_reminder_enabled' => false, 'overdue_reminder_enabled' => false]);
        $this->expectSmsCount(0);

        $this->runChecker();

        $this->assertEquals(0, Ticket::query()->count());
    }

    public function testCommandSendsUpcomingReminderOnce(): void {
        $this->setUpCustomer();
        $applianceRate = $this->createApplianceWithDueRate(3, ['upcoming_reminder_enabled' => true]);
        $this->expectSmsCount(1);

        $this->runChecker();
        $this->runChecker();

        $this->assertEquals(ApplianceRate::REMIND_UPCOMING_SENT, $applianceRate->refresh()->remind);
    }

    public function testCommandSendsOverdueReminderOnceAfterUpcomingReminder(): void {
        $this->setUpCustomer();
        $applianceRate = $this->createApplianceWithDueRate(
            -14,
            ['upcoming_reminder_enabled' => true, 'overdue_reminder_enabled' => true],
            remind: ApplianceRate::REMIND_UPCOMING_SENT,
        );
        $this->expectSmsCount(1);

        $this->runChecker();
        $this->runChecker();

        $this->assertEquals(ApplianceRate::REMIND_OVERDUE_SENT, $applianceRate->refresh()->remind);
    }

    public function testCommandSkipsOverdueRateWhenOnlyUpcomingReminderEnabled(): void {
        $this->setUpCustomer();
        $this->createApplianceWithDueRate(-14, ['upcoming_reminder_enabled' => true]);
        $this->expectSmsCount(0);

        $this->runChecker();
    }

    public function testCommandSkipsUpcomingRateWhenOnlyOverdueReminderEnabled(): void {
        $this->setUpCustomer();
        $this->createApplianceWithDueRate(3, ['overdue_reminder_enabled' => true]);
        $this->expectSmsCount(0);

        $this->runChecker();
    }

    public function testCommandDoesNotProcessFutureRatesOutsideWindow(): void {
        $this->setUpCustomer();
        $this->createApplianceWithDueRate(30, ['upcoming_reminder_enabled' => true, 'overdue_reminder_enabled' => true]);
        $this->expectSmsCount(0);

        $this->runChecker();
    }

    public function testCommandSkipsRatesOverdueBeyondCatchUpWindow(): void {
        $this->setUpCustomer();
        $this->createApplianceWithDueRate(-30, ['overdue_reminder_enabled' => true]);
        $this->expectSmsCount(0);

        $this->runChecker();
    }

    public function testCommandSkipsPaidRates(): void {
        $this->setUpCustomer();
        $this->createApplianceWithDueRate(3, ['upcoming_reminder_enabled' => true], remaining: 0);
        $this->expectSmsCount(0);

        $this->runChecker();
    }

    public function testCommandAppliesReminderSettingsOnlyToTheirAppliance(): void {
        $this->setUpCustomer();
        $this->createApplianceWithDueRate(30, ['upcoming_reminder_enabled' => true]);
        $this->createApplianceWithDueRate(3, ['upcoming_reminder_enabled' => true, 'remind_rate' => 1]);
        $this->expectSmsCount(0);

        $this->runChecker();
    }

    public function testCommandCreatesTicketWithCorrectOwner(): void {
        $this->setUpCustomer();
        $this->createApplianceWithDueRate(3, ['upcoming_reminder_enabled' => true, 'create_ticket' => true]);
        $this->expectSmsCount(1);

        $this->runChecker();

        $this->assertEquals(1, Ticket::query()->count());
        $ticket = Ticket::query()->first();
        $this->assertNotNull($ticket);
        $this->assertEquals('person', $ticket->owner_type);
        $this->assertEquals($this->person->id, $ticket->owner_id);
    }

    public function testCommandSendsReminderWithoutTicketWhenCreateTicketDisabled(): void {
        $this->setUpCustomer();
        $this->createApplianceWithDueRate(3, ['upcoming_reminder_enabled' => true, 'create_ticket' => false]);
        $this->expectSmsCount(1);

        $this->runChecker();

        $this->assertEquals(0, Ticket::query()->count());
    }

    public function testEnabledRemindRatesIncludeEitherReminderType(): void {
        $this->createTestData();
        $applianceType = ApplianceTypeFactory::new()->create();
        $upcomingOnlyAppliance = ApplianceFactory::new()->create(['appliance_type_id' => $applianceType->id]);
        $overdueOnlyAppliance = ApplianceFactory::new()->create(['appliance_type_id' => $applianceType->id]);
        $disabledAppliance = ApplianceFactory::new()->create(['appliance_type_id' => $applianceType->id]);

        SmsApplianceRemindRateFactory::new()->create([
            'appliance_id' => $upcomingOnlyAppliance->id,
            'upcoming_reminder_enabled' => true,
        ]);
        SmsApplianceRemindRateFactory::new()->create([
            'appliance_id' => $overdueOnlyAppliance->id,
            'overdue_reminder_enabled' => true,
        ]);
        SmsApplianceRemindRateFactory::new()->create([
            'appliance_id' => $disabledAppliance->id,
        ]);

        $enabledApplianceIds = resolve(SmsApplianceRemindRateService::class)->getApplianceRemindRates()->pluck('appliance_id')->all();

        $this->assertEqualsCanonicalizing([$upcomingOnlyAppliance->id, $overdueOnlyAppliance->id], $enabledApplianceIds);
    }
}
