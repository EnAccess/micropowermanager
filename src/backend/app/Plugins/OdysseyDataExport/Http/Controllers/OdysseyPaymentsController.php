<?php

namespace App\Plugins\OdysseyDataExport\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PaymentHistory;
use App\Models\Token;
use App\Plugins\OdysseyDataExport\Services\OdysseyPaymentTransformer;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OdysseyPaymentsController extends Controller {
    public function index(Request $request, OdysseyPaymentTransformer $transformer): JsonResponse {
        $fromParam = $request->query('FROM');
        $toParam = $request->query('TO');
        $siteId = $request->query('SITE_ID');
        if (!$fromParam || !$toParam) {
            return response()->json(['payments' => [], 'errors' => 'FROM and TO are required ISO8601 timestamps'], 400);
        }

        $from = CarbonImmutable::parse($fromParam);
        $to = CarbonImmutable::parse($toParam);
        if ((int) $to->diffInHours($from) > 24) {
            return response()->json(['payments' => [], 'errors' => 'Range must be <= 24 hours'], 400);
        }

        $query = PaymentHistory::query()
            ->with([
                'paidFor' => function ($morphTo) {
                    $morphTo->morphWith([
                        Token::class => ['device.person.miniGrid', 'device.address.geo'],
                    ]);
                },
                'payer.addresses',
                'payer.miniGrid',
                'transaction.originalTransaction',
            ])
            ->whereBetween('created_at', [$from, $to]);

        // Filter by siteId (mini-grid name) if provided
        if ($siteId) {
            $query->whereHas('payer.miniGrid', function ($q) use ($siteId) {
                $q->where('name', $siteId);
            });
        }

        $payments = $query->get()
            ->map($transformer->transform(...))
            ->values();

        return response()->json([
            'payments' => $payments,
            'errors' => '',
        ]);
    }
}
