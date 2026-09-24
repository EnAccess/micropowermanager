<?php

namespace App\Console\Commands;

use App\Models\ApplianceRate;
use App\Models\SmsApplianceRemindRate;
use App\Models\Ticket\TicketCategory;
use App\Models\User;
use App\Services\MainSettingsService;
use App\Services\SmsApplianceRemindRateService;
use App\Services\SmsService;
use App\Services\TicketService;
use App\Services\TicketUserService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Illuminate\Database\Eloquent\Collection;

class ApplianceRateChecker extends AbstractSharedCommand {
    protected $signature = 'appliance-rate:check';
    protected $description = 'Checks if any appliance rate is due and creates a ticket and reminds the customer';
    private const int OVERDUE_CATCH_UP_DAYS = 7;

    public function __construct(
        private ApplianceRate $applianceRate,
        private TicketService $ticketService,
        private SmsApplianceRemindRateService $smsApplianceRemindRateService,
        private User $user,
        private TicketCategory $label,
        private MainSettingsService $mainSettingsService,
        private TicketUserService $ticketUserService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $smsApplianceRemindRates = $this->getApplianceRemindRates();

        if ($smsApplianceRemindRates->isEmpty()) {
            $this->info('No enabled appliance reminder configurations found.');

            return;
        }

        $this->info('Found '.$smsApplianceRemindRates->count().' enabled reminder configuration(s).');
        $this->newLine();

        $upcomingRates = $this->remindUpComingRates($smsApplianceRemindRates);
        $overdueRates = $this->findOverDueRates($smsApplianceRemindRates);

        $this->newLine();
        $this->displaySummary($upcomingRates, $overdueRates);
    }

    /**
     * @param Collection<int, SmsApplianceRemindRate> $smsApplianceRemindRates
     *
     * @return Collection<int, ApplianceRate>
     */
    private function remindUpComingRates(Collection $smsApplianceRemindRates): Collection {
        $allUpcoming = new Collection();
        $smsApplianceRemindRates
            ->filter(fn (SmsApplianceRemindRate $smsApplianceRemindRate): bool => $smsApplianceRemindRate->upcoming_reminder_enabled)
            ->each(function (SmsApplianceRemindRate $smsApplianceRemindRate) use ($allUpcoming) {
                $upcomingRates = $this->applianceRate::with([
                    'appliancePerson.appliance.applianceType',
                    'appliancePerson.person.addresses',
                ])
                    ->whereHas(
                        'appliancePerson',
                        fn ($query) => $query->where('appliance_id', $smsApplianceRemindRate->appliance_id)
                    )
                    ->whereHas(
                        'appliancePerson.person.addresses',
                        fn ($query) => $query->where('is_primary', 1)
                    )
                    ->whereBetween('due_date', [
                        now()->toDateString(),
                        now()->addDays($smsApplianceRemindRate->remind_rate)->toDateString(),
                    ])
                    ->where('remaining', '>', 0)
                    ->where('remind', ApplianceRate::REMIND_NONE)
                    ->get();
                $allUpcoming->push(...$upcomingRates);
                $this->sendReminders($upcomingRates, $smsApplianceRemindRate, SmsTypes::APPLIANCE_RATE);
            });

        return $allUpcoming;
    }

    /**
     * @param Collection<int, SmsApplianceRemindRate> $smsApplianceRemindRates
     *
     * @return Collection<int, ApplianceRate>
     */
    private function findOverDueRates(Collection $smsApplianceRemindRates): Collection {
        $allOverdue = new Collection();
        $smsApplianceRemindRates
            ->filter(fn (SmsApplianceRemindRate $smsApplianceRemindRate): bool => $smsApplianceRemindRate->overdue_reminder_enabled)
            ->each(function (SmsApplianceRemindRate $smsApplianceRemindRate) use ($allOverdue) {
                $latestDueDate = now()->subDays($smsApplianceRemindRate->overdue_remind_rate);
                $overDueRates = $this->applianceRate::with([
                    'appliancePerson.appliance.applianceType',
                    'appliancePerson.person.addresses',
                ])
                    ->whereHas(
                        'appliancePerson',
                        fn ($query) => $query->where('appliance_id', $smsApplianceRemindRate->appliance_id)
                    )
                    ->whereHas(
                        'appliancePerson.person.addresses',
                        fn ($query) => $query->where('is_primary', 1)
                    )
                    ->whereBetween('due_date', [
                        $latestDueDate->copy()->subDays(self::OVERDUE_CATCH_UP_DAYS)->toDateString(),
                        $latestDueDate->toDateString(),
                    ])
                    ->where('remaining', '>', 0)
                    ->where('remind', '<', ApplianceRate::REMIND_OVERDUE_SENT)
                    ->get();
                $allOverdue->push(...$overDueRates);
                $this->sendReminders($overDueRates, $smsApplianceRemindRate, SmsTypes::OVER_DUE_APPLIANCE_RATE);
            });

        return $allOverdue;
    }

    /**
     * @param Collection<int, ApplianceRate> $upcomingRates
     * @param Collection<int, ApplianceRate> $overdueRates
     */
    private function displaySummary(Collection $upcomingRates, Collection $overdueRates): void {
        if ($upcomingRates->isEmpty() && $overdueRates->isEmpty()) {
            $this->info('No upcoming or overdue appliance rates found.');

            return;
        }

        if ($upcomingRates->isNotEmpty()) {
            $this->info('Upcoming Rates ('.$upcomingRates->count().'):');
            $this->table(
                ['Appliance', 'Customer', 'Due Date', 'Remaining'],
                $upcomingRates->map(fn (ApplianceRate $rate): array => [
                    $rate->appliancePerson->appliance->applianceType->name ?? 'N/A',
                    $rate->appliancePerson->person->name ?? 'N/A',
                    (string) $rate->due_date,
                    $rate->remaining,
                ])->all(),
            );
        }

        if ($overdueRates->isNotEmpty()) {
            $this->info('Overdue Rates ('.$overdueRates->count().'):');
            $this->table(
                ['Appliance', 'Customer', 'Due Date', 'Remaining'],
                $overdueRates->map(fn (ApplianceRate $rate): array => [
                    $rate->appliancePerson->appliance->applianceType->name ?? 'N/A',
                    $rate->appliancePerson->person->name ?? 'N/A',
                    (string) $rate->due_date,
                    $rate->remaining,
                ])->all(),
            );
        }
    }

    private function sendReminderSms(ApplianceRate $applianceRate, int $smsType): void {
        $smsService = app()->make(SmsService::class);
        $smsService->sendSms($applianceRate, $smsType, SmsConfigs::class);
    }

    /**
     * @param Collection<int, ApplianceRate> $dueApplianceRates
     */
    private function sendReminders(Collection $dueApplianceRates, SmsApplianceRemindRate $smsApplianceRemindRate, int $smsType): void {
        $isOverdue = $smsType === SmsTypes::OVER_DUE_APPLIANCE_RATE;
        $dueApplianceRates->each(function (ApplianceRate $dueApplianceRate) use ($smsApplianceRemindRate, $smsType, $isOverdue) {
            $this->sendReminderSms($dueApplianceRate, $smsType);
            $dueApplianceRate->remind = $isOverdue ? ApplianceRate::REMIND_OVERDUE_SENT : ApplianceRate::REMIND_UPCOMING_SENT;
            $dueApplianceRate->update();
            if ($smsApplianceRemindRate->create_ticket) {
                $this->createReminderTicket($dueApplianceRate, $isOverdue);
            }
        });
    }

    private function createReminderTicket(ApplianceRate $applianceRate, bool $overDue = false): void {
        $currency = $this->mainSettingsService->getAll()->first()->currency;
        $creator = $this->user->newQuery()->oldest()->firstOrFail();
        // reformat due date if it is set
        if ($overDue) {
            $category = $this->label->newQuery()->firstOrCreate(
                ['label_name' => 'Payments Issue'],
                ['label_color' => '#e74c3c', 'out_source' => false],
            );
            $description = 'Customer didn\'t pay '.$applianceRate->remaining.$currency.' on '.$applianceRate->due_date;
        } else {
            $category = $this->label->newQuery()->firstOrCreate(
                ['label_name' => 'Customer Follow Up'],
                ['label_color' => '#3498db', 'out_source' => false],
            );
            $description =
                'Customer should pay '.$applianceRate->remaining.$currency.' until '.$applianceRate->due_date;
        }

        $this->ticketService->create(
            title: $applianceRate->appliancePerson->appliance->name.' rate reminder',
            content: $description,
            categoryId: $category->id,
            assignedId: $this->ticketUserService->findOrCreateByUser($creator)->id,
            dueDate: (string) $applianceRate->due_date === '1970-01-01' ? null : (string) $applianceRate->due_date,
            owner: $applianceRate->appliancePerson()->first()->person()->first(),
            creator: $creator,
        );
    }

    /**
     * @return Collection<int, SmsApplianceRemindRate>
     */
    private function getApplianceRemindRates(): Collection {
        return $this->smsApplianceRemindRateService->getApplianceRemindRates();
    }
}
