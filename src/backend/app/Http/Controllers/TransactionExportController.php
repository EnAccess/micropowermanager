<?php

namespace App\Http\Controllers;

use App\Services\ExportServices\TransactionExportService;
use App\Services\MainSettingsService;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionExportController {
    public function __construct(
        private TransactionService $transactionService,
        private TransactionExportService $transactionExportService,
        private MainSettingsService $mainSettingsService,
    ) {}

    public function download(Request $request): StreamedResponse|JsonResponse {
        $format = $request->input('format', 'excel');

        if ($format === 'csv') {
            return $this->downloadCsv($request);
        }

        if ($format === 'json') {
            return $this->downloadJson($request);
        }

        return $this->downloadExcel($request);
    }

    public function downloadExcel(
        Request $request,
    ): StreamedResponse {
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

        return Storage::download($pathToSpreadSheet, 'transaction_export_'.now()->format('Ymd_His').'.xlsx');
    }

    public function downloadCsv(Request $request): StreamedResponse {
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

        return Storage::download($csvPath, 'transaction_export_'.now()->format('Ymd_His').'.csv');
    }

    public function downloadJson(Request $request): JsonResponse {
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
