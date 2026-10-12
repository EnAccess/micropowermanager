<?php

namespace App\Plugins\OdysseyDataExport\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ApplianceRate;
use App\Models\MainSettings;
use App\Models\Meter\Meter;
use App\Models\PaymentHistory;
use App\Models\Person\Person;
use App\Models\Token;
use App\Plugins\OdysseyDataExport\Services\OdysseyPaymentTransformer;
use App\Plugins\OdysseyDataExport\Services\OdysseySettingService;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Plugins / Odyssey Data Export', 'API endpoints for exporting payment data to Odyssey')]
class OdysseyPaymentsController extends Controller {
    public function index(
        Request $request,
        OdysseyPaymentTransformer $transformer,
        OdysseySettingService $settingService,
    ): JsonResponse {
        $fromParam = $request->query('FROM');
        $toParam = $request->query('TO');
        $siteId = $request->query('SITE_ID');
        if (!$fromParam || !$toParam) {
            return response()->json(['payments' => [], 'errors' => 'FROM and TO are required ISO8601 timestamps'], 400);
        }

        $from = CarbonImmutable::parse($fromParam);
        $to = CarbonImmutable::parse($toParam);
        if ($to->lessThan($from) || $from->diffInHours($to) > 24) {
            return response()->json(['payments' => [], 'errors' => 'TO must be after FROM and the range must be <= 24 hours'], 400);
        }

        $withMeterConnectionType = function (MorphTo $morphTo): void {
            $morphTo->morphWith([Meter::class => ['connectionType']]);
        };

        $query = PaymentHistory::query()
            ->with([
                'paidFor' => function ($morphTo) use ($withMeterConnectionType): void {
                    $morphTo->morphWith([
                        Token::class => ['device.device' => $withMeterConnectionType, 'device.geo'],
                        ApplianceRate::class => ['appliancePerson.device.device' => $withMeterConnectionType, 'appliancePerson.device.geo'],
                    ]);
                },
                'payer' => function ($morphTo) use ($withMeterConnectionType): void {
                    $morphTo->morphWith([
                        Person::class => ['addresses.city', 'devices.device' => $withMeterConnectionType],
                    ]);
                },
                'transaction.originalTransaction',
            ])
            ->whereBetween('created_at', [$from, $to]);

        if ($siteId) {
            $query->whereHasMorph('payer', [Person::class], fn (Builder $payerQuery) => $payerQuery->whereHas(
                'addresses',
                fn (Builder $addressQuery) => $addressQuery->where('is_primary', 1)->whereHas(
                    'city.miniGrid',
                    fn (Builder $miniGridQuery) => $miniGridQuery->where(is_numeric($siteId) ? 'id' : 'name', $siteId)
                )
            ));
        }

        $currency = MainSettings::query()->value('currency') ?? '';
        $financingId = $settingService->getSettings()->financing_id?->value;

        $payments = $query->get()
            ->map(fn (PaymentHistory $payment) => $transformer->transform($payment, $currency, $financingId))
            ->values();

        return response()->json([
            'payments' => $payments,
            'errors' => '',
        ]);
    }
}
