<?php

namespace App\Services\ExportServices;

use App\Helpers\MailHelper;
use App\Models\AppliancePerson;
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

        $this->worksheet->fromArray(
            $this->exportingData->toArray(),
            null,
            'A2'
        );

        $columnWidths = ['A' => 25, 'B' => 25, 'C' => 40, 'D' => 20, 'E' => 12, 'F' => 15, 'G' => 20, 'H' => 20, 'I' => 25];
        foreach ($columnWidths as $column => $width) {
            $this->worksheet->getColumnDimension($column)->setWidth($width);
        }
    }

    public function setExportingData(): void {
        $this->exportingData = $this->outstandingDebtsData->map(function (ApplianceRate $applianceRate): array {
            $appliancePerson = $applianceRate->appliancePerson;
            $totals = $this->loanTotals($appliancePerson);

            return [
                $appliancePerson->person->name.' '.$appliancePerson->person->surname,
                $appliancePerson->appliance->name,
                $appliancePerson->device_serial,
                $applianceRate->due_date,
                $this->currency,
                $applianceRate->remaining,
                $totals['down_payment'],
                $totals['total_paid'],
                $totals['total_remaining'],
            ];
        });
    }

    /**
     * The three loan-level totals repeated on every outstanding-rate row of the same purchase.
     * The down payment is itself stored as a fully-paid rate, so it is already part of `total_paid`.
     *
     * @return array{down_payment: float, total_paid: float, total_remaining: float}
     */
    private function loanTotals(AppliancePerson $appliancePerson): array {
        return [
            'down_payment' => (float) ($appliancePerson->down_payment ?? 0),
            'total_paid' => (float) $appliancePerson->rates->sum(fn (ApplianceRate $rate): int => $rate->rate_cost - $rate->remaining),
            'total_remaining' => (float) $appliancePerson->rates->sum('remaining'),
        ];
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
                $this->mailHelper->sendPlain(
                    $user->email,
                    'Outstanding debts report - '.$reportDate->format('d-m-Y'),
                    'Please find attached the outstanding debts report. This report is generated on '.CarbonImmutable::now()->format('d-m-Y').'.',
                    $path,
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
        $jsonDataTransform = $this->outstandingDebtsData->map(function (ApplianceRate $applianceRate): array {
            $appliancePerson = $applianceRate->appliancePerson;
            $totals = $this->loanTotals($appliancePerson);

            return [
                'customer' => $appliancePerson->person->name.' '.$appliancePerson->person->surname,
                'appliance' => $appliancePerson->appliance->name,
                'device_serial' => $appliancePerson->device_serial,
                'due_date' => $applianceRate->due_date,
                'currency' => $this->currency,
                'remaining' => $this->readable($applianceRate->remaining),
                'down_payment' => $this->readable($totals['down_payment']),
                'total_paid' => $this->readable($totals['total_paid']),
                'total_remaining' => $this->readable($totals['total_remaining']),
            ];
        });

        return $jsonDataTransform->all();
    }
}
