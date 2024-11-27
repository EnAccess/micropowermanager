<?php

namespace MPM\OutstandingDebts;

use App\Helpers\MailHelper;
use App\Models\User;
use App\Services\AbstractExportService;
use App\Services\ApplianceRateService;
use App\Services\UserService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class OutstandingDebtsExportService extends AbstractExportService {
    public function __construct(
        private readonly UserService $userService,
        private ApplianceRateService $applianceService,
        private ApplianceRateService $applianceRateService,
        private MailHelper $mailHelper,
    ) {}

    private Collection $outstandingDebtsData;

    public function writeOutstandingDebtsData(): void {
        $this->setActivatedSheet('Sheet1');

        foreach ($this->exportingData as $key => $value) {
            $this->worksheet->setCellValue('A'.($key + 2), $value[0]);
            $this->worksheet->setCellValue('B'.($key + 2), $value[1]);
            $this->worksheet->setCellValue('C'.($key + 2), $value[2]);
            $this->worksheet->setCellValue('D'.($key + 2), $value[3]);
            $this->worksheet->setCellValue('E'.($key + 2), $value[4]);
        }

        foreach ($this->worksheet->getColumnIterator() as $column) {
            $this->worksheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
    }

    public function setExportingData(): void {
        $this->exportingData = $this->outstandingDebtsData->map(function ($applianceRate) {
            return [
                $applianceRate->assetPerson->person->name.' '.$applianceRate->assetPerson->person->surname,
                $applianceRate->assetPerson->asset->name,
                $applianceRate->assetPerson->device_serial,
                $applianceRate->due_date,
                $applianceRate->remaining,
            ];
        });
    }

    public function setOutstandingDebtsData($outstandingDebtsData): void {
        $this->outstandingDebtsData = $outstandingDebtsData;
    }

    public function getTemplatePath(): string {
        return storage_path('appliance/export_outstanding_debts_template.xlsx');
    }

    public function createReport(CarbonImmutable $toDate): string {
        $currency = $this->applianceRateService->getCurrencyFromMainSettings();

        $data = $this->applianceService->queryOutstandingDebtsByApplianceRates($toDate)->get();
        $this->createSpreadSheetFromTemplate($this->getTemplatePath());
        $this->setCurrency($currency);
        $this->setOutstandingDebtsData($data);
        $this->setExportingData();
        $this->writeOutstandingDebtsData();

        return $this->saveSpreadSheet();
    }

    public function sendApplianceDebtsAsEmail(): void {
        $reportDate = CarbonImmutable::now()->endOfWeek()->endOfDay();
        $path = $this->createReport($reportDate);

        $this->userService->getUsers()
            ->each(function (User $user) use ($path, $reportDate) {
                $this->mailHelper->sendPlain(
                    $user->email,
                    'Outstanding debts report - '.$reportDate->format('d-m-Y'),
                    'Please find attached the outstanding debts report. This report is generated on '.CarbonImmutable::now()->format('d-m-Y').'.',
                    $path
                );
            });
    }

    public function getPrefix(): string {
        return 'OutstandingDebtsExport';
    }
}
