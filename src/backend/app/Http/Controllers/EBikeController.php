<?php

namespace App\Http\Controllers;

use App\Events\NewLogEvent;
use App\Http\Requests\StoreEBikeRequest;
use App\Http\Resources\ApiResource;
use App\Services\AppliancePersonService;
use App\Services\ManufacturerService;
use Illuminate\Http\Request;
use MPM\Device\DeviceService;
use MPM\EBike\EBikeDeviceService;
use MPM\EBike\EBikeService;

class EBikeController extends Controller {
    public function __construct(
        private DeviceService $deviceService,
        private EBikeService $eBikeService,
        private EBikeDeviceService $eBikeDeviceService,
        private ManufacturerService $manufacturerService,
        private AppliancePersonService $appliancePersonService,
    ) {}

    public function index(Request $request): ApiResource {
        $limit = $request->input('per_page');

        return ApiResource::make($this->eBikeService->getAll($limit));
    }

    public function store(StoreEBikeRequest $request): ApiResource {
        $eBikeData = $request->all();
        $deviceData = [
            'device_serial' => $eBikeData['serial_number'],
            'person_id' => null,
        ];

        $device = $this->deviceService->make($deviceData);
        $eBike = $this->eBikeService->create($eBikeData);
        $this->eBikeDeviceService->setAssigned($device);
        $this->eBikeDeviceService->setAssignee($eBike);
        $this->eBikeDeviceService->assign();
        $this->deviceService->save($device);

        return ApiResource::make($eBike->load(['manufacturer', 'appliance', 'device.person']));
    }

    public function search(Request $request): ApiResource {
        $term = $request->input('term');
        $paginate = $request->input('paginate') ?? 1;

        return ApiResource::make($this->eBikeService->search($term, $paginate));
    }

    public function show(string $serialNumber): ApiResource {
        return ApiResource::make($this->eBikeService->getBySerialNumber($serialNumber));
    }

    public function switch(Request $request): ApiResource {
        $serialNumber = $request->input('serial_number');
        $manufacturerName = $request->input('manufacturer_name');
        $status = $request->input('status');
        $manufacturer = $this->manufacturerService->getByName($manufacturerName);
        $manufacturerApi = resolve($manufacturer->api_name);
        $manufacturerApi->switchDevice($serialNumber, $status);
        $creatorId = auth('api')->user()->id;
        $appliancePerson = $this->appliancePersonService->getBySerialNumber($serialNumber);
        event(new NewLogEvent([
            'user_id' => $creatorId,
            'affected' => $appliancePerson,
            'action' => 'Bike ('.$serialNumber.') is set as '.$status.' manually.',
        ]));

        return ApiResource::make($this->eBikeService->getBySerialNumber($serialNumber));
    }
}
