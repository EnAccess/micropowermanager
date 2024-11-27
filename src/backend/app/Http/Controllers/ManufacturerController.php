<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManufacturerRequest;
use App\Http\Resources\ApiResource;
use App\Services\AddressesService;
use App\Services\ManufacturerAddressService;
use App\Services\ManufacturerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManufacturerController extends Controller {
    public function __construct(
        private ManufacturerService $manufacturerService,
        private ManufacturerAddressService $manufacturerAddressService,
        private AddressesService $addressService,
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return ApiResource
     */
    public function index(Request $request): ApiResource {
        $limit = $request->input('per_page');

        return ApiResource::make($this->manufacturerService->getAll($limit));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ManufacturerRequest $request
     *
     * @return JsonResponse
     */
    public function store(ManufacturerRequest $request): JsonResponse {
        $manufacturerData = $this->manufacturerService->createManufacturerDataFromRequest($request);
        $addressData = $this->addressService->createAddressDataFromRequest($request);
        $manufacturer = $this->manufacturerService->create($manufacturerData);
        $address = $this->addressService->make($addressData);
        $this->manufacturerAddressService->setAssigned($address);
        $this->manufacturerAddressService->setAssignee($manufacturer);
        $this->manufacturerAddressService->assign();
        $this->addressService->save($address);

        return ApiResource::make($manufacturer)->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param int $manufacturerId
     *
     * @return ApiResource
     */
    public function show($manufacturerId): ApiResource {
        return ApiResource::make($this->manufacturerService->getById($manufacturerId));
    }
}
