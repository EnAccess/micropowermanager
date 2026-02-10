<?php

namespace App\Http\Controllers;

use App\Http\Requests\TariffCreateRequest;
use App\Http\Resources\ApiResource;
use App\Services\TariffService;
use App\Utils\TariffPriceCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TariffController extends Controller {
    public function __construct(
        private TariffService $tariffService,
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

        return ApiResource::make($this->tariffService->getAll($limit));
    }

    /**
     * Detail.
     *
     * @urlParam     id int required
     *
     * @responseFile responses/tariffs/tariff.detail.json
     */
    public function show(Request $request, int $tariffId): ApiResource {
        return ApiResource::make($this->tariffService->getById($tariffId));
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
        $tariffData = $request->only(['name', 'factor', 'currency', 'price', 'minimum_purchase_amount']);
        $newTariff = $this->tariffService->create($tariffData);

        $calculator = resolve(TariffPriceCalculator::class);
        $calculator->calculateTotalPrice($newTariff, $request);

        return ApiResource::make($this->tariffService->getById($newTariff->id))->response()->setStatusCode(201);
    }

    public function update(int $tariffId, TariffCreateRequest $request): ApiResource {
        $tariff = $this->tariffService->getById($tariffId);
        $tariffData = [
            'name' => $request->input('name'),
            'factor' => $request->input('factor'),
            'currency' => $request->input('currency'),
            'price' => $request->input('price'),
            'total_price' => $request->input('price'),
            'minimum_purchase_amount' => $request->input('minimum_purchase_amount'),
        ];

        $tariff = $this->tariffService->update($tariff, $tariffData);
        $calculator = resolve(TariffPriceCalculator::class);
        $calculator->calculateTotalPrice($tariff, $request);

        return ApiResource::make($tariff);
    }

    public function destroy(int $tariffId): ?bool {
        $tariff = $this->tariffService->getById($tariffId);

        return $this->tariffService->delete($tariff);
    }

    public function updateTariff(int $tariffId, int $changeId): ApiResource {
        $result = $this->tariffService->changeMetersTariff($tariffId, $changeId);

        return ApiResource::make($result);
    }

    public function updateForMeter(string $meterSerial, int $tariffId): ApiResource {
        $result = $this->tariffService->changeMeterTariff($meterSerial, $tariffId);

        return ApiResource::make($result);
    }

    /**
     * Display a list of meters which using a particular tariff.
     */
    public function showUsageCount(int $tariffId): ApiResource {
        return ApiResource::make($this->tariffService->getCountById($tariffId));
    }
}
