<?php

namespace Inensus\DalyBms\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Events\NewLogEvent;
use App\Models\AssetRate;
use App\Models\User;
use App\Services\SmsApplianceRemindRateService;
use App\Traits\ScheduledPluginCommand;
use Carbon\Carbon;
use Inensus\DalyBms\Modules\Api\DalyBmsApi;
use MPM\EBike\EBikeService;

class CheckPayments extends AbstractSharedCommand {
    use ScheduledPluginCommand;
    public const MPM_PLUGIN_ID = 16;
    public const E_BIKE = 2;
    public const MANUFACTURER_NAME = 'DalyBms';

    protected $signature = 'daly-bms:check-payments';
    protected $description = 'Checks payments for e-bikes.';

    public function __construct(
        private AssetRate $assetRate,
        private SmsApplianceRemindRateService $smsApplianceRemindRateService,
        private DalyBmsApi $dalyBmsApi,
        private EBikeService $eBikeService,
        private User $user,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        if (!$this->checkForPluginStatusIsActive(self::MPM_PLUGIN_ID)) {
            return;
        }

        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# Daly BMS Package #');
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('check-payments command started at '.$startedAt);

        try {
            $this->assetRate::with([
                'assetPerson.asset.smsReminderRate',
                'assetPerson.person.addresses',
                'assetPerson.asset',
            ])
                ->whereHas('assetPerson.asset', function ($q) {
                    $q->where('asset_type_id', self::E_BIKE);
                })
                ->whereDate('due_date', '>=', now()->format('Y-m-d'))
                ->where('remaining', '>', 0)
                ->where('remind', '>', 0)
                ->each(fn ($installment) => $this->lockTheBike($installment));
        } catch (\Exception $e) {
            $this->warn('check-payments command is failed. message => '.$e->getMessage());
        }

        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        $this->info('Took '.$totalTime.' seconds.');
        $this->info('#############################');
    }

    private function lockTheBike($installment): bool {
        $eBike = $this->eBikeService->getBySerialNumber($installment->assetPerson->device_serial);

        if ($eBike->manufacturer->name != self::MANUFACTURER_NAME) {
            return false;
        }
        $this->info('Locking the bike with id: '.$eBike->serial_number);
        $this->dalyBmsApi->switchDevice($eBike->serial_number, false);

        $status = $eBike->status;
        $serialNumber = $eBike->serial_number;
        $updatingData = [
            'status' => str_replace('ACCON', 'ACCOFF', $status),
        ];
        $this->eBikeService->update(
            $eBike,
            $updatingData
        );

        $creator = $this->user->newQuery()->firstOrCreate([
            'name' => 'System',
        ]);

        event(new NewLogEvent([
            'user_id' => $creator->id,
            'affected' => $eBike,
            'action' => "Bike ($serialNumber) is locked by system, due to overdue payment.",
        ]));

        return true;
    }
}
