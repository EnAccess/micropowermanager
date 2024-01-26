<?php

namespace App\Http\Controllers;

use App\Services\ApplianceRateService;
use Illuminate\Http\Request;
use MPM\OutstandingDebts\Export\OutstandingDebtsExportService;

class OutstandingDebtsExportController
{
    private string $path = __DIR__ . '/../../modules/OutstandingDebts/Export';

    public function __construct(
        private OutstandingDebtsExportService $outstandingDebtsExportService,
        private ApplianceRateService $applianceRateService
    ) {
    }

    public function download(
        Request $request,
    ) {
        $data = $this->applianceRateService->getOutstandingDebtsByApplianceRates();
        $this->outstandingDebtsExportService->createSpreadSheetFromTemplate($this->outstandingDebtsExportService->getTemplatePath());
        $currency = $this->applianceRateService->getCurrencyFromMainSettings();
        $this->outstandingDebtsExportService->setCurrency($currency);
        $this->outstandingDebtsExportService->setOutstandingDebtsData($data);
        $this->outstandingDebtsExportService->setExportingData();
        $this->outstandingDebtsExportService->writeOutstandingDebtsData();
        $this->outstandingDebtsExportService->saveSpreadSheet($this->path);

        return response()->download($this->path . '/' .
            $this->outstandingDebtsExportService->getRecentlyCreatedSpreadSheetId() . '.xlsx');
    }
}
