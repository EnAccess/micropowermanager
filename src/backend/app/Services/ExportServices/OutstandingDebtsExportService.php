<?php

namespace App\Services\ExportServices;

use App\Helpers\MailHelper;
use App\Models\ApplianceRate;
use App\Models\User;
use App\Services\ApplianceRateService;
use App\Services\UserService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class OutstandingDebtsExportService extends AbstractExportService {
    public function __construct(
        private readonly UserService $userService,
        private ApplianceRateService $applianceRateService,
        private MailHelper $mailHelper,
    ) {}

    /**
     * @var Collection<int, ApplianceRate>
     */
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
        $this->exportingData = $this->outstandingDebtsData->map(fn (ApplianceRate $applianceRate): array => [
            $applianceRate->appliancePerson->person->name.' '.$applianceRate->appliancePerson->person->surname,
            $applianceRate->appliancePerson->appliance->name,
            $applianceRate->appliancePerson->device_serial,
            $applianceRate->due_date,
            $applianceRate->remaining,
        ]);
    }

    /**
     * @param Collection<int, ApplianceRate> $outstandingDebtsData
     */
    public function setOutstandingDebtsData(Collection $outstandingDebtsData): void {
        $this->outstandingDebtsData = $outstandingDebtsData;
    }

    public function getTemplatePath(): string {
        return resource_path('templates/export_outstanding_debts_template.xlsx');
    }

    public function createReport(CarbonImmutable $toDate): string {
        $currency = $this->applianceRateService->getCurrencyFromMainSettings();

        $data = $this->applianceRateService->queryOutstandingDebtsByApplianceRates($toDate)->get();
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

        $this->userService->getUsersToSendOutstandingDebtsReport()
            ->each(function (User $user) use ($path, $reportDate) {
                $delaySeconds = 3 * $user->id;
                $this->mailHelper->sendPlain(
                    $user->email,
                    'Outstanding debts report - '.$reportDate->format('d-m-Y'),
                    'Please find attached the outstanding debts report. This report is generated on '.CarbonImmutable::now()->format('d-m-Y').'.',
                    $path,
                    $delaySeconds
                );
            });
    }

    public function getPrefix(): string {
        return 'OutstandingDebtsExport';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function exportDataToArray(): array {
        if ($this->outstandingDebtsData->isEmpty()) {
            return [];
        }
        // TODO: support some form of pagination to limit the data to be exported using json
        // transform exporting data to JSON structure for outstanding debts export
        $jsonDataTransform = $this->outstandingDebtsData->map(fn (ApplianceRate $applianceRate): array => [
            'customer' => $applianceRate->appliancePerson->person->name.' '.$applianceRate->appliancePerson->person->surname,
            'appliance' => $applianceRate->appliancePerson->appliance->name,
            'device_serial' => $applianceRate->appliancePerson->device_serial,
            'due_date' => $applianceRate->due_date,
            'remaining' => $this->readable($applianceRate->remaining),
            'currency' => $this->currency,
        ]);

        return $jsonDataTransform->all();
    }
}
