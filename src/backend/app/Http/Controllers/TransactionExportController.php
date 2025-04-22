<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use MPM\Transaction\Export\TransactionExportService;
use MPM\Transaction\TransactionService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TransactionExportController {
    public function __construct(
        private TransactionService $transactionService,
        private TransactionExportService $transactionExportService,
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
        );
        $this->transactionExportService->createSpreadSheetFromTemplate($this->transactionExportService->getTemplatePath());
        $this->transactionExportService->setCurrency($currency);
        $this->transactionExportService->setTimeZone($timeZone);
        $this->transactionExportService->setTransactionData($data);
        $this->transactionExportService->setExportingData();
        $this->transactionExportService->writeTransactionData();
        $path = $this->transactionExportService->saveSpreadSheet();

        return response()->download($path, 'transactions'.$fromDate.'-'.$toDate.'.xlsx');
    }

    public function downloadCsv(Request $request): BinaryFileResponse {
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
        );
        $this->transactionExportService->setCurrency($currency);
        $this->transactionExportService->setTimeZone($timeZone);
        $this->transactionExportService->setTransactionData($data);
        $this->transactionExportService->setExportingData();
        $headers = ['Status', 'Payment Service', 'Customer', 'Serial Number', 'Amount', 'Type', 'Date'];
        $csvPath = $this->transactionExportService->saveCsv($headers);

        return response()->download($csvPath, 'transactions'.$fromDate.'-'.$toDate.'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
