<?php

namespace App\Http\Controllers;

use App\Http\Requests\CityRequest;
use App\Http\Requests\DeleteCityRequest;
use App\Http\Requests\UpdateCityRequest;
use App\Http\Resources\ApiResource;
use App\Http\Resources\LinkedAddressResource;
use App\Services\CityService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class CityController extends Controller {
    public function __construct(
        private CityService $cityService,
    ) {}

    public function index(Request $request): ApiResource {
        $limit = $request->input('limit');

        return ApiResource::make($this->cityService->getAll($limit));
    }

    /**
     * List cities (customer registration app).
     *
     * Alias of `GET /api/cities` for the customer registration app.
     */
    #[Group('Customer Registration App')]
    #[\Deprecated(message: 'use `GET /api/cities` instead')]
    public function indexForCustomerRegistrationApp(Request $request): ApiResource {
        return ApiResource::make($this->cityService->getAll($request->input('limit')));
    }

    public function show(int $cityId, Request $request): ApiResource {
        $relation = $request->input('relation');

        if ($relation) {
            return ApiResource::make($this->cityService->getByIdWithRelation($cityId, ['location', 'country']));
        }

        return ApiResource::make($this->cityService->getById($cityId));
    }

    public function update(int $cityId, UpdateCityRequest $request): ApiResource {
        $city = $this->cityService->getById($cityId);

        return ApiResource::make($this->cityService->update($city, $request->validated()));
    }

    public function store(CityRequest $request): ApiResource {
        return ApiResource::make($this->cityService->create($request->validated()));
    }

    /**
     * List the addresses linked to a village.
     */
    public function addresses(int $cityId, Request $request): AnonymousResourceCollection {
        $city = $this->cityService->getById($cityId);

        return LinkedAddressResource::collection($this->cityService->getLinkedAddresses($city, $request->integer('limit') ?: null));
    }

    /**
     * Delete a village.
     *
     * Pass `reassign_addresses_to` to move every address linked to this village over
     * to another village first — which is how two duplicate villages get merged.
     */
    public function destroy(int $cityId, DeleteCityRequest $request): JsonResponse {
        $city = $this->cityService->getById($cityId);
        $targetCityId = $request->integer('reassign_addresses_to');

        DB::connection('tenant')->transaction(function () use ($city, $targetCityId): void {
            if ($targetCityId) {
                $this->cityService->moveAddressesTo($city, $this->cityService->getById($targetCityId));
            }

            $this->cityService->delete($city);
        });

        return response()->json(['message' => 'Village deleted.']);
    }
}
