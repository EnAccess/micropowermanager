<?php

namespace App\Http\Controllers;

use App\Services\MainSettingsService;
use App\Services\TransactionExportService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TransactionExportController {
    public function __construct(
        private TransactionService $transactionService,
        private TransactionExportService $transactionExportService,
        private MainSettingsService $mainSettingsService,
    ) {}

    public function download(Request $request): BinaryFileResponse {
        $format = $request->get('format', 'excel');

        if ($format === 'csv') {
            return $this->downloadCsv($request);
        }

        return $this->downloadExcel($request);
    }

    public function downloadExcel(
        Request $request,
    ): BinaryFileResponse {
        $deviceType = $request->get('deviceType', 'all');
        $serialNumber = $request->get('serial_number');
        $tariffId = $request->get('tariff');
        $transactionProvider = $request->get('provider', 'all');
        $status = $request->get('status');
        $fromDate = $request->get('from');
        $toDate = $request->get('to');

        $mainSettings = $this->mainSettingsService->getAll()->first();
        $this->transactionExportService->setCurrency($mainSettings->currency);
        $data = $this->transactionService->search(
            $deviceType,
            $serialNumber,
            $tariffId,
            $transactionProvider,
            (int) $status,
            $fromDate,
            $toDate,
        );
        $this->transactionExportService->createSpreadSheetFromTemplate($this->transactionExportService->getTemplatePath());
        $this->transactionExportService->setTransactionData($data);
        $this->transactionExportService->setExportingData();
        $this->transactionExportService->writeTransactionData();
        $pathToSpreadSheet = $this->transactionExportService->saveSpreadSheet();

        $path = Storage::disk('local')->path($pathToSpreadSheet);

        return response()->download($path, 'transaction_export_'.now()->format('Ymd_His').'.xlsx');
    }

    public function downloadCsv(Request $request): BinaryFileResponse {
        $deviceType = $request->get('deviceType', 'all');
        $transactionProvider = $request->get('provider', 'all');
        $status = $request->get('status');
        $fromDate = $request->get('from');
        $toDate = $request->get('to');

        $mainSettings = $this->mainSettingsService->getAll()->first();
        $this->transactionExportService->setCurrency($mainSettings->currency);
        $data = $this->transactionService->search(
            $deviceType,
            null,
            null,
            $transactionProvider,
            (int) $status,
            $fromDate,
            $toDate,
        );
        $this->transactionExportService->setTransactionData($data);
        $this->transactionExportService->setExportingData();
        $headers = ['Status', 'Payment Service', 'Customer', 'Device Serial Number', 'Device Type', 'Amount', 'Type', 'Date'];
        $csvPath = $this->transactionExportService->saveCsv($headers);

        $path = Storage::disk('local')->path($csvPath);

        return response()->download($path, 'transaction_export_'.now()->format('Ymd_His').'.csv');
    }
}
