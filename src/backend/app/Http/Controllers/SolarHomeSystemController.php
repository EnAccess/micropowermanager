<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSolarHomeSystemRequest;
use App\Http\Resources\ApiResource;
use App\Services\PaymentHistoryService;
use Illuminate\Http\Request;
use MPM\Device\DeviceService;
use MPM\SolarHomeSystem\SolarHomeSystemDeviceService;
use MPM\SolarHomeSystem\SolarHomeSystemService;

class SolarHomeSystemController extends Controller {
    public function __construct(
        private DeviceService $deviceService,
        private SolarHomeSystemService $solarHomeSystemService,
        private SolarHomeSystemDeviceService $solarHomeSystemDeviceService,
        private PaymentHistoryService $paymentHistoryService,
    ) {}

    public function index(Request $request): ApiResource {
        $limit = $request->input('per_page');

        return ApiResource::make($this->solarHomeSystemService->getAll($limit));
    }

    public function store(StoreSolarHomeSystemRequest $request): ApiResource {
        $solarHomeSystemData = $request->all();
        $deviceData = [
            'device_serial' => $solarHomeSystemData['serial_number'],
            'person_id' => $solarHomeSystemData['person_id'],
        ];

        $device = $this->deviceService->make($deviceData);
        $solarHomeSystem = $this->solarHomeSystemService->create($solarHomeSystemData);
        $this->solarHomeSystemDeviceService->setAssigned($device);
        $this->solarHomeSystemDeviceService->setAssignee($solarHomeSystem);
        $this->solarHomeSystemDeviceService->assign();
        $this->deviceService->save($device);

        return ApiResource::make($solarHomeSystem->load(['manufacturer', 'appliance', 'device.person']));
    }

    public function search(Request $request): ApiResource {
        $term = $request->input('term');
        $paginate = $request->input('paginate') ?? 1;

        return ApiResource::make($this->solarHomeSystemService->search($term, $paginate));
    }

    public function show(int $id): ApiResource {
        return ApiResource::make($this->solarHomeSystemService->getById($id));
    }

    public function transactions(int $id): ApiResource {
        $shs = $this->solarHomeSystemService->getById($id);
        $paginate = request('paginate') ?? 15;

        return ApiResource::make($this->paymentHistoryService->getBySerialNumber($shs->serial_number, $paginate));
    }
}
