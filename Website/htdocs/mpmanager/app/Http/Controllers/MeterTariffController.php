<?php

namespace App\Http\Controllers;

use App\Http\Requests\TariffCreateRequest;
use App\Http\Resources\ApiResource;
use App\Models\Meter\MeterTariff;
use App\Services\MeterTariffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeterTariffController extends Controller
{
    public function __construct(private MeterTariffService $meterTariffService)
    {
    }

    /**
     * List
     * a list of all tariffs.
     * The list is paginated and each page contains 15 results
     *
     * @responseFile responses/tariffs/tariffs.list.json
     * @return       ApiResource
     */
    public function index(Request $request): ApiResource
    {
        $limit = $request->get('limit');

        return ApiResource::make($this->meterTariffService->getAll($limit));
    }

    /**
     * Detail
     *
     * @urlParam     id int required
     * @responseFile responses/tariffs/tariff.detail.json
     * @param MeterTariff $tariff
     * @return       ApiResource
     */
    public function show(Request $request, $meterTariffId): ApiResource
    {

        return ApiResource::make($this->meterTariffService->getById($meterTariffId));
    }

    /**
     * Create
     *
     * @bodyParam name string required
     * @bodyParam factor int. The factor between two different sub tariffs. Like day/night sub-tariffs.
     * @bodyParam currency string
     * @bodyParam price int required. kWh-price X 100 . The last two digits are basically the amount after comma.
     * @param TariffCreateRequest $request
     * @return ApiResource
     */
    public function store(TariffCreateRequest $request): JsonResponse
    {
        $meterTariffData = $request->only(['name', 'factor', 'currency', 'price']);
        $newTariff = $this->meterTariffService->create($meterTariffData);
        $tariff = MeterTariff::with(
            [
                'accessRate',
                'pricingComponent',
                'socialTariff',
                'tou'
            ]
        )->find($newTariff->id);

        return ApiResource::make($this->meterTariffService->getById($newTariff->id))->response()->setStatusCode(201);
    }

    public function update($meterTariffId, TariffCreateRequest $request): ApiResource
    {
        $meterTariff = $this->meterTariffService->getById($meterTariffId);
        $meterTariffData = [
            'name' => $request->input('name'),
            'factor' => $request->input('factor'),
            'currency' => $request->input('currency'),
            'price' => $request->input('price'),
            'total_price' => $request->input('price'),
        ];
        $result = $this->meterTariffService->update($meterTariff, $meterTariffData);

        return ApiResource::make($result);
    }

    public function destroy($meterTariffId): ?bool
    {
        $meterTariff = $this->meterTariffService->getById($meterTariffId);

        return $this->meterTariffService->delete($meterTariff);
    }
}
