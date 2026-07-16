<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionExportRequest;
use App\Services\ExportServices\AbstractExportService;
use App\Services\ExportServices\TransactionExportService;
use App\Services\MainSettingsService;
use App\Services\TransactionService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Group('Export', 'Export data as Excel, CSV, or JSON.', weight: 11)]
class TransactionExportController {
    public function __construct(
        private TransactionService $transactionService,
        private TransactionExportService $transactionExportService,
        private MainSettingsService $mainSettingsService,
    ) {}

    /**
     * Export transactions.
     *
     * Downloads transactions as an Excel or CSV file, or returns them as JSON.
     *
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public function download(TransactionExportRequest $request): StreamedResponse|JsonResponse {
        $format = $request->input('format', 'excel');

        if ($format === 'csv') {
            return $this->downloadCsv($request);
        }

        if ($format === 'json') {
            return $this->downloadJson($request);
        }

        return $this->downloadExcel($request);
    }

    public function downloadExcel(TransactionExportRequest $request): StreamedResponse {
        $deviceType = $request->input('deviceType', 'all');
        $serialNumber = $request->input('serial_number');
        $tariffId = $request->input('tariff');
        $transactionProvider = $request->input('provider', 'all');
        $status = $request->input('status');
        $fromDate = $request->input('from');
        $toDate = $request->input('to');

        $mainSettings = $this->mainSettingsService->getAll()->first();
        $this->transactionExportService->setCurrency($mainSettings->currency);
        $data = $this->transactionService->search(
            $deviceType,
            $serialNumber,
            $tariffId,
            $transactionProvider,
            is_null($status) ? null : (int) $status,
            $fromDate,
            $toDate,
        );
        $this->transactionExportService->createSpreadSheetFromTemplate($this->transactionExportService->getTemplatePath());
        $this->transactionExportService->setTransactionData($data);
        $this->transactionExportService->setExportingData();
        $this->transactionExportService->writeTransactionData();
        $pathToSpreadSheet = $this->transactionExportService->saveSpreadSheet();

        return Storage::download($pathToSpreadSheet, 'transaction_export_'.now()->format('Ymd_His').'.xlsx', ['Content-Type' => AbstractExportService::XLSX_CONTENT_TYPE]);
    }

    public function downloadCsv(TransactionExportRequest $request): StreamedResponse {
        $deviceType = $request->input('deviceType', 'all');
        $transactionProvider = $request->input('provider', 'all');
        $status = $request->input('status');
        $fromDate = $request->input('from');
        $toDate = $request->input('to');

        $mainSettings = $this->mainSettingsService->getAll()->first();
        $this->transactionExportService->setCurrency($mainSettings->currency);
        $data = $this->transactionService->search(
            $deviceType,
            null,
            null,
            $transactionProvider,
            is_null($status) ? null : (int) $status,
            $fromDate,
            $toDate,
        );
        $this->transactionExportService->setTransactionData($data);
        $this->transactionExportService->setExportingData();
        $headers = ['Status', 'Payment Service', 'Customer', 'Device Serial Number', 'Device Type', 'Amount', 'Type', 'Date'];
        $csvPath = $this->transactionExportService->saveCsv($headers);

        return Storage::download($csvPath, 'transaction_export_'.now()->format('Ymd_His').'.csv', ['Content-Type' => AbstractExportService::CSV_CONTENT_TYPE]);
    }

    public function downloadJson(TransactionExportRequest $request): JsonResponse {
        $deviceType = $request->input('deviceType', 'all');
        $serialNumber = $request->input('serial_number');
        $transactionProvider = $request->input('provider', 'all');
        $status = $request->input('status');
        $fromDate = $request->input('from');
        $toDate = $request->input('to');

        $mainSettings = $this->mainSettingsService->getAll()->first();
        $this->transactionExportService->setCurrency($mainSettings->currency);
        $data = $this->transactionService->search(
            $deviceType,
            $serialNumber,
            null,
            $transactionProvider,
            is_null($status) ? null : (int) $status,
            $fromDate,
            $toDate,
        );
        $this->transactionExportService->setTransactionData($data);
        $jsonData = $this->transactionExportService->exportDataToArray();

        return response()->json([
            'data' => $jsonData,
            'meta' => [
                'total' => count($jsonData),
                'currency' => $mainSettings->currency,
                'filters' => [
                    'device_type' => $deviceType,
                    'serial_number' => $serialNumber,
                    'provider' => $transactionProvider,
                    'status' => $status,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                ],
                'exported_at' => now()->toISOString(),
            ],
        ]);
    }
}
