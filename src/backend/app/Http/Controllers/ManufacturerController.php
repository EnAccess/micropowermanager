<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManufacturerRequest;
use App\Http\Resources\ApiResource;
use App\Services\AddressesService;
use App\Services\ManufacturerAddressService;
use App\Services\ManufacturerService;
use Dedoc\Scramble\Attributes\Group;
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
     */
    public function index(Request $request): ApiResource {
        $limit = $request->input('per_page');
        $type = $request->input('type');

        return ApiResource::make($this->manufacturerService->getAll($limit, $type));
    }

    /**
     * List manufacturers (customer registration app).
     *
     * Alias of `GET /api/manufacturers` for the customer registration app.
     *
     * @deprecated use `GET /api/manufacturers` instead
     */
    #[Group('Customer Registration App')]
    public function indexForCustomerRegistrationApp(Request $request): ApiResource {
        return ApiResource::make(
            $this->manufacturerService->getAll($request->input('per_page'), $request->input('type'))
        );
    }

    /**
     * Store a newly created resource in storage.
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
     */
    public function show(int $manufacturerId): ApiResource {
        return ApiResource::make($this->manufacturerService->getById($manufacturerId));
    }
}
