<?php

namespace App\Console\Commands;

use App\Models\ApplianceRate;
use App\Models\SmsApplianceRemindRate;
use App\Models\User;
use App\Services\MainSettingsService;
use App\Services\SmsApplianceRemindRateService;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Illuminate\Database\Eloquent\Collection;
use Inensus\Ticket\Models\TicketCategory;
use Inensus\Ticket\Services\TicketService;

class ApplianceRateChecker extends AbstractSharedCommand {
    protected $signature = 'appliance-rate:check';
    protected $description = 'Checks if any appliance rate is due and creates a ticket and reminds the customer';

    public function __construct(
        private ApplianceRate $applianceRate,
        private TicketService $ticketService,
        private SmsApplianceRemindRateService $smsApplianceRemindRateService,
        private User $user,
        private TicketCategory $label,
        private MainSettingsService $mainSettingsService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->remindUpComingRates();
        $this->findOverDueRates();
    }

    private function remindUpComingRates(): void {
        $smsApplianceRemindRates = $this->getApplianceRemindRates();
        $smsApplianceRemindRates->each(function (SmsApplianceRemindRate $smsApplianceRemindRate) {
            $dueApplianceRates = $this->applianceRate::with([
                'appliancePerson.appliance.smsReminderRate',
                'appliancePerson.person.addresses',
            ])
                ->whereBetween('due_date', [
                    now()->subDays($smsApplianceRemindRate->remind_rate)->toDateString(),
                    now()->toDateString(),
                ])
                ->where('remaining', '>', 0)
                ->whereHas(
                    'appliancePerson.person.addresses',
                    function ($q) {
                        $q->where('is_primary', 1);
                    }
                )
                ->get();
            echo "\n".count($dueApplianceRates).' upcoming rates found'."\n";
            $this->sendReminders($dueApplianceRates, SmsTypes::APPLIANCE_RATE);
        });
    }

    private function findOverDueRates(): void {
        $smsApplianceRemindRates = $this->getApplianceRemindRates();
        $smsApplianceRemindRates->each(function (SmsApplianceRemindRate $smsApplianceRemindRate) {
            $overDueRates = $this->applianceRate::with(['appliancePerson.appliance', 'appliancePerson.person.addresses'])
                ->whereBetween('due_date', [
                    now()->toDateString(),
                    now()->addDays($smsApplianceRemindRate->overdue_remind_rate)->toDateString(),
                ])
                ->where('remaining', '>', 0)
                ->where('remind', 0)
                ->get();

            echo "\n".count($overDueRates).' overdue rates found'."\n";
            $this->sendReminders($overDueRates, SmsTypes::OVER_DUE_APPLIANCE_RATE);
        });
    }

    private function sendReminderSms(ApplianceRate $applianceRate): void {
        $smsService = app()->make(SmsService::class);
        $smsService->sendSms($applianceRate->toArray(), SmsTypes::APPLIANCE_RATE, SmsConfigs::class);
    }

    /**
     * @param Collection<int, ApplianceRate> $dueApplianceRates
     */
    private function sendReminders(Collection $dueApplianceRates, int $smsType): void {
        $dueApplianceRates->each(function (ApplianceRate $dueApplianceRate) use ($smsType) {
            $this->sendReminderSms($dueApplianceRate);
            if ($smsType === SmsTypes::OVER_DUE_APPLIANCE_RATE) {
                $dueApplianceRate->remind = 1;
                $dueApplianceRate->update();
            }
            $this->createReminderTicket($dueApplianceRate);
        });
    }

    private function createReminderTicket(ApplianceRate $applianceRate, bool $overDue = false): void {
        $currency = $this->mainSettingsService->getAll()->first()->currency;
        // create ticket for customer service
        $creator = $this->user->newQuery()->firstOrCreate([
            'name' => 'System',
        ]);
        // reformat due date if it is set
        if ($overDue) {
            $category = $this->label->newQuery()->firstOrCreate([
                'label_name' => 'Payments Issue',
            ]);
            $description = 'Customer didn\'t pay '.$applianceRate->remaining.$currency.' on '.$applianceRate->due_date;
        } else {
            $category = $this->label->newQuery()->firstOrCreate([
                'label_name' => 'Customer Follow Up',
            ]);
            $description =
                'Customer should pay '.$applianceRate->remaining.$currency.' until '.$applianceRate->due_date;
        }

        $this->ticketService->create(
            title: $applianceRate->appliancePerson->appliance->name.' rate reminder',
            content: $description,
            categoryId: $category->id,
            assignedId: $creator->id,
            dueDate: $applianceRate->due_date->toDateString() === '1970-01-01' ? null : $applianceRate->due_date,
            owner: $applianceRate->appliancePerson()->first()->person()->first(),
        );
    }

    /**
     * @return Collection<int, SmsApplianceRemindRate>
     */
    private function getApplianceRemindRates(): Collection {
        return $this->smsApplianceRemindRateService->getApplianceRemindRates();
    }
}
