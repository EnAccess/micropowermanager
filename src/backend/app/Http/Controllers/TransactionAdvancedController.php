<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;
use MPM\Transaction\TransactionService;

class TransactionAdvancedController extends Controller {
    public function __construct(
        private TransactionService $transactionService,
    ) {}

    public function searchAdvanced(Request $request): ApiResource {
        $type = $request->input('deviceType') ?: 'meter';
        $serialNumber = $request->input('serial_number');
        $tariffId = $request->input('tariff');
        $transactionProvider = $request->input('provider');
        $status = $request->input('status');
        $fromDate = $request->input('from');
        $toDate = $request->input('to');
        $limit = (int) ($request->input('per_page') ?? '15');
        $transactionService = $this->transactionService->getRelatedService($type);

        return ApiResource::make($transactionService->search(
            $serialNumber,
            $tariffId,
            $transactionProvider,
            $status,
            $fromDate,
            $toDate,
            $limit
        ));
    }

    /**
     * @param int $period
     *
     * @return array<string, mixed>
     */
    public function compare(int $period): array {
        $comparisonPeriod = $this->transactionService->determinePeriod($period);
        // get transactions for both current and previous periods
        $transactions = $this->transactionService->getByComparisonPeriod($comparisonPeriod);
        // get data for the current period
        $currentTransactions = $this->transactionService->getAnalysis($transactions['current']->toArray()) ?? $this->transactionService->getEmptyCompareResult();
        // get data for the previous period
        $pastTransactions = $this->transactionService->getAnalysis($transactions['past']->toArray()) ?? $this->transactionService->getEmptyCompareResult();

        // compare current period with the previous period
        return [
            'success' => true,
            'current' => $currentTransactions,
            'past' => $pastTransactions,
            'analytics' => $this->transactionService->comparePeriods($currentTransactions, $pastTransactions),
        ];
    }
}
