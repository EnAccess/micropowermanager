<?php

namespace App\Console\Commands;

use App\Jobs\SmsProcessor;
use App\Models\AssetRate;
use App\Models\User;
use App\Services\SmsApplianceRemindRateService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Inensus\Ticket\Models\TicketCategory;
use Inensus\Ticket\Services\TicketService;

class AssetRateChecker extends AbstractSharedCommand
{
    protected $signature = 'asset-rate:check';
    protected $description = 'Checks if any asset rate is due and creates a ticket and reminds the customer';

    public function __construct(
        private AssetRate $assetRate,
        private TicketService $ticketService,
        private SmsApplianceRemindRateService $smsApplianceRemindRateService,
        private User $user,
        private TicketCategory $label
    ) {
        parent::__construct();
    }


    public function runInCompanyScope(): void
    {
        $this->remindUpComingRates();
        $this->findOverDueRates();
    }


    private function findOverDueRates(): void
    {
        $smsApplianceRemindRates = $this->getApplianceRemindRates();
        $smsApplianceRemindRates->each(function ($smsApplianceRemindRate) {
            $overDueRates = $this->assetRate::with(['assetPerson.assetType', 'assetPerson.person.addresses'])
                ->whereDate(
                    'due_date',
                    '>=',
                    Carbon::parse('due_date')->addDays($smsApplianceRemindRate->overdue_remind_rate)
                )
                ->where('remaining', '>', 0)
                ->where('remind', 0)
                ->get();
            echo "\n" . count($overDueRates) . ' overdue rates found' . "\n";
            $this->sendReminders($overDueRates, SmsTypes::OVER_DUE_APPLIANCE_RATE);
        });
    }

    private function remindUpComingRates(): void
    {
        $smsApplianceRemindRates = $this->getApplianceRemindRates();
        $smsApplianceRemindRates->each(function ($smsApplianceRemindRate) {
            $remindDay = Carbon::now()->subDays($smsApplianceRemindRate->remind_rate)->format('Y-m-d');
            $dueAssetRates = $this->assetRate::with([
                'assetPerson.assetType.smsReminderRate',
                'assetPerson.person.addresses'
            ])
                ->whereDate('due_date', '>=', $remindDay)
                ->where('remaining', '>', 0)
                ->whereHas(
                    'assetPerson.person.addresses',
                    static function ($q) {
                        $q->where('is_primary', 1);
                    }
                )
                ->get();
            echo "\n" . count($dueAssetRates) . ' upcoming rates found' . "\n";
            $this->sendReminders($dueAssetRates, SmsTypes::APPLIANCE_RATE);
        });
    }

    private function sendReminderSms(AssetRate $assetRate): void
    {
        SmsProcessor::dispatch(
            $assetRate,
            SmsTypes::APPLIANCE_RATE,
            SmsConfigs::class
        )->allOnConnection('database')->onQueue(\config('services.queues.sms'));
    }

    private function sendReminders($dueAssetRates, $smsType)
    {
        $dueAssetRates->each(function ($dueAssetRate) use ($smsType) {
            $this->sendReminderSms($dueAssetRate);
            if ($smsType === SmsTypes::OVER_DUE_APPLIANCE_RATE) {
                $dueAssetRate->remind = 1;
                $dueAssetRate->update();
            }
            $this->createReminderTicket($dueAssetRate);
        });
    }

    private function createReminderTicket(AssetRate $assetRate, $overDue = false): void
    {
        //create ticket for customer service
        $creator = $this->user->newQuery()->where('name', 'System')->first();
        //reformat due date if it is set
        if ($overDue) {
            $category = $this->label->newQuery()->where('label_name', 'Payments Issue')->first();
            $description = 'Customer didn\'t pay ' . $assetRate->remaining . 'TZS on ' . $assetRate->due_date;
        } else {
            $category = $this->label->newQuery()->where('label_name', 'Customer Follow Up')->first();
            $description = 'Customer should pay ' . $assetRate->remaining . 'TZS until ' . $assetRate->due_date;
        }


        $this->ticketService->create(
            title: $assetRate->assetPerson->assetType->name . ' rate reminder',
            content: $description,
            categoryId: $category->id,
            assignedId: $creator?->id,
            dueDate: $assetRate->due_date === '1970-01-01' ? null : $assetRate->due_date,
            owner: $assetRate->assetPerson()->first()->person()->first(),
        );
    }

    private function getApplianceRemindRates(): Collection|array
    {
        return $this->smsApplianceRemindRateService->getApplianceRemindRates();
    }
}
