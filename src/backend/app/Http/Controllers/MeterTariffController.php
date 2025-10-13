<?php

namespace App\Http\Controllers;

use App\Http\Requests\TariffCreateRequest;
use App\Http\Resources\ApiResource;
use App\Services\MeterTariffService;
use App\Utils\TariffPriceCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeterTariffController extends Controller {
    public function __construct(
        private MeterTariffService $meterTariffService,
    ) {}

    /**
     * List
     * a list of all tariffs.
     * The list is paginated and each page contains 15 results.
     *
     * @responseFile responses/tariffs/tariffs.list.json
     */
    public function index(Request $request): ApiResource {
        $limit = $request->get('limit');

        return ApiResource::make($this->meterTariffService->getAll($limit));
    }

    /**
     * Detail.
     *
     * @urlParam     id int required
     *
     * @responseFile responses/tariffs/tariff.detail.json
     */
    public function show(Request $request, int $meterTariffId): ApiResource {
        return ApiResource::make($this->meterTariffService->getById($meterTariffId));
    }

    /**
     * Create.
     *
     * @bodyParam name string required
     * @bodyParam factor int. The factor between two different sub tariffs. Like day/night sub-tariffs.
     * @bodyParam currency string
     * @bodyParam price int required.
     */
    public function store(TariffCreateRequest $request): JsonResponse {
        $meterTariffData = $request->only(['name', 'factor', 'currency', 'price', 'minimum_purchase_amount']);
        $newTariff = $this->meterTariffService->create($meterTariffData);

        $calculator = resolve(TariffPriceCalculator::class);
        $calculator->calculateTotalPrice($newTariff, $request);

        return ApiResource::make($this->meterTariffService->getById($newTariff->id))->response()->setStatusCode(201);
    }

    public function update(int $meterTariffId, TariffCreateRequest $request): ApiResource {
        $meterTariff = $this->meterTariffService->getById($meterTariffId);
        $meterTariffData = [
            'name' => $request->input('name'),
            'factor' => $request->input('factor'),
            'currency' => $request->input('currency'),
            'price' => $request->input('price'),
            'total_price' => $request->input('price'),
            'minimum_purchase_amount' => $request->input('minimum_purchase_amount'),
        ];

        $meterTariff = $this->meterTariffService->update($meterTariff, $meterTariffData);
        $calculator = resolve(TariffPriceCalculator::class);
        $calculator->calculateTotalPrice($meterTariff, $request);

        return ApiResource::make($meterTariff);
    }

    public function destroy(int $meterTariffId): ?bool {
        $meterTariff = $this->meterTariffService->getById($meterTariffId);

        return $this->meterTariffService->delete($meterTariff);
    }

    public function updateTariff(int $meterTariffId, int $changeId): ApiResource {
        $result = $this->meterTariffService->changeMetersTariff($meterTariffId, $changeId);

        return ApiResource::make($result);
    }

    public function updateForMeter(string $meterSerial, int $tariffId): ApiResource {
        $result = $this->meterTariffService->changeMeterTariff($meterSerial, $tariffId);

        return ApiResource::make($result);
    }

    /**
     * Display a list of meters which using a particular tariff.
     */
    public function showUsageCount(int $meterTariffId): ApiResource {
        return ApiResource::make($this->meterTariffService->getCountById($meterTariffId));
    }
}
