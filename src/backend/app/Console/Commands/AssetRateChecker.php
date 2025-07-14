<?php

namespace App\Console\Commands;

use App\Models\AssetRate;
use App\Models\SmsApplianceRemindRate;
use App\Models\User;
use App\Services\MainSettingsService;
use App\Services\SmsApplianceRemindRateService;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Inensus\Ticket\Models\TicketCategory;
use Inensus\Ticket\Services\TicketService;

class AssetRateChecker extends AbstractSharedCommand {
    protected $signature = 'asset-rate:check';
    protected $description = 'Checks if any asset rate is due and creates a ticket and reminds the customer';

    public function __construct(
        private AssetRate $assetRate,
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
            $dueAssetRates = $this->assetRate::with([
                'assetPerson.asset.smsReminderRate',
                'assetPerson.person.addresses',
            ])
                ->whereBetween('due_date', [
                    now()->subDays($smsApplianceRemindRate->remind_rate)->toDateString(),
                    now()->toDateString(),
                ])
                ->where('remaining', '>', 0)
                ->whereHas(
                    'assetPerson.person.addresses',
                    function ($q) {
                        $q->where('is_primary', 1);
                    }
                )
                ->get();
            echo "\n".count($dueAssetRates).' upcoming rates found'."\n";
            $this->sendReminders($dueAssetRates, SmsTypes::APPLIANCE_RATE);
        });
    }

    private function findOverDueRates(): void {
        $smsApplianceRemindRates = $this->getApplianceRemindRates();
        $smsApplianceRemindRates->each(function (SmsApplianceRemindRate $smsApplianceRemindRate) {
            $overDueRates = $this->assetRate::with(['assetPerson.asset', 'assetPerson.person.addresses'])
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

    private function sendReminderSms(AssetRate $assetRate): void {
        $smsService = app()->make(SmsService::class);
        $smsService->sendSms($assetRate->toArray(), SmsTypes::APPLIANCE_RATE, SmsConfigs::class);
    }

    private function sendReminders($dueAssetRates, $smsType) {
        $dueAssetRates->each(function ($dueAssetRate) use ($smsType) {
            $this->sendReminderSms($dueAssetRate);
            if ($smsType === SmsTypes::OVER_DUE_APPLIANCE_RATE) {
                $dueAssetRate->remind = 1;
                $dueAssetRate->update();
            }
            $this->createReminderTicket($dueAssetRate);
        });
    }

    private function createReminderTicket(AssetRate $assetRate, $overDue = false): void {
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
            $description = 'Customer didn\'t pay '.$assetRate->remaining.$currency.' on '.$assetRate->due_date;
        } else {
            $category = $this->label->newQuery()->firstOrCreate([
                'label_name' => 'Customer Follow Up',
            ]);
            $description =
                'Customer should pay '.$assetRate->remaining.$currency.' until '.$assetRate->due_date;
        }

        $this->ticketService->create(
            title: $assetRate->assetPerson->asset->name.' rate reminder',
            content: $description,
            categoryId: $category->id,
            assignedId: $creator->id,
            dueDate: $assetRate->due_date === '1970-01-01' ? null : $assetRate->due_date,
            owner: $assetRate->assetPerson()->first()->person()->first(),
        );
    }

    private function getApplianceRemindRates(): SupportCollection|Collection {
        return $this->smsApplianceRemindRateService->getApplianceRemindRates();
    }
}
