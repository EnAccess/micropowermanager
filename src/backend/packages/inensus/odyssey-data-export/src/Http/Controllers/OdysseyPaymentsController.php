<?php

namespace Inensus\OdysseyDataExport\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PaymentHistory;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inensus\OdysseyDataExport\Services\OdysseyPaymentTransformer;

class OdysseyPaymentsController extends Controller {
    public function index(Request $request, OdysseyPaymentTransformer $transformer): JsonResponse {
        $fromParam = $request->query('FROM');
        $toParam = $request->query('TO');
        if (!$fromParam || !$toParam) {
            return response()->json(['payments' => [], 'errors' => 'FROM and TO are required ISO8601 timestamps'], 400);
        }

        $from = CarbonImmutable::parse($fromParam);
        $to = CarbonImmutable::parse($toParam);
        if ($to->diffInHours($from) > 24) {
            return response()->json(['payments' => [], 'errors' => 'Range must be <= 24 hours'], 400);
        }

        $payments = PaymentHistory::query()
            ->with(['paidFor', 'payer.addresses', 'transaction'])
            ->whereBetween('created_at', [$from, $to])
            ->get()
            ->map(fn (PaymentHistory $p): array => $transformer->transform($p))
            ->values();

        return response()->json([
            'payments' => $payments,
            'errors' => '',
        ]);
    }
}
