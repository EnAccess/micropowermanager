<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use MPM\Transaction\Export\TransactionExportService;
use MPM\Transaction\TransactionService;

class TransactionExportController
{
    private string $path = __DIR__ . '/../../modules/Transaction/Export';
    public function __construct(
        private TransactionService $transactionService,
        private TransactionExportService $transactionExportService
    ) {
    }

    public function download(
        Request $request,
    ) {
        $type = $request->get('deviceType') ?: 'meter';
        $serialNumber = $request->get('serial_number');
        $tariffId = $request->get('tariff');
        $transactionProvider = $request->get('provider');
        $status = $request->get('status');
        $fromDate = $request->get('from');
        $toDate = $request->get('to');
        $currency = $request->get('currency');
        $timeZone = $request->get('timeZone');

        if ($timeZone) {
            $timeZone = urldecode($timeZone);
        }

        $transactionService = $this->transactionService->getRelatedService($type);
        $data = $transactionService->search(
            $serialNumber,
            $tariffId,
            $transactionProvider,
            $status,
            $fromDate,
            $toDate,
            null
        );
        $this->transactionExportService->createSpreadSheetFromTemplate($this->transactionExportService->getTemplatePath());
        $this->transactionExportService->setCurrency($currency);
        $this->transactionExportService->setTimeZone($timeZone);
        $this->transactionExportService->setTransactionData($data);
        $this->transactionExportService->setExportingData();
        $this->transactionExportService->writeTransactionData();
        $this->transactionExportService->saveSpreadSheet($this->path);

        return response()->download($this->path . '/' .
            $this->transactionExportService->getRecentlyCreatedSpreadSheetId() . '.xlsx');
    }
}
