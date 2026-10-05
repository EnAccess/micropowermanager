<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Jobs\OperatorDashboardRebuildJob;
use App\Services\OperatorDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OperatorDashboardController extends Controller {
    public function __construct(private OperatorDashboardService $operatorDashboardService) {}

    public function index(): ApiResource {
        return ApiResource::make($this->operatorDashboardService->platformSnapshot()->toArray());
    }

    public function show(int $companyId): ApiResource {
        return ApiResource::make($this->operatorDashboardService->tenantSnapshot($companyId)->toDetailArray());
    }

    public function invoice(Request $request, int $companyId): StreamedResponse {
        $request->validate(['month' => ['required', 'date_format:Y-m']]);

        $monthStart = Carbon::createFromFormat('Y-m', (string) $request->query('month'))->startOfMonth();

        if ($monthStart->isAfter(Carbon::now()->startOfMonth())) {
            throw ValidationException::withMessages(['month' => 'The billing month cannot be in the future.']);
        }

        $invoiceData = $this->operatorDashboardService->invoiceData($companyId, $monthStart);

        return response()->streamDownload(function () use ($invoiceData): void {
            $output = fopen('php://output', 'w');
            foreach ($invoiceData->toCsvRows() as $row) {
                fputcsv($output, $row, escape: '\\');
            }
            fclose($output);
        }, $invoiceData->filename(), ['Content-Type' => 'text/csv']);
    }

    /**
     * Queues a rebuild and answers immediately with the freshness stamp the client
     * should poll against. Building inline would fan out across every tenant
     * database inside a web request.
     */
    public function refresh(): JsonResponse {
        if ($this->operatorDashboardService->startRefreshing()) {
            dispatch(new OperatorDashboardRebuildJob());
        }

        return response()->json([
            'data' => [
                'refreshing' => true,
                'generated_at' => $this->operatorDashboardService->generatedAt()?->toIso8601String(),
            ],
        ], 202);
    }
}
