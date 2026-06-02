<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSolarHomeSystemRequest;
use App\Http\Requests\UpdateSolarHomeSystemRequest;
use App\Http\Resources\ApiResource;
use App\Services\DeviceService;
use App\Services\PaymentHistoryService;
use App\Services\SolarHomeSystemDeviceService;
use App\Services\SolarHomeSystemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $serialNumbers = $request->input('serial_numbers');
        $manufacturerId = $request->input('manufacturer_id');
        $applianceId = $request->input('appliance_id');
        $personId = $request->input('person_id');

        $created = DB::connection('tenant')->transaction(function () use ($serialNumbers, $manufacturerId, $applianceId, $personId): array {
            $solarHomeSystems = [];
            foreach ($serialNumbers as $serialNumber) {
                $device = $this->deviceService->make([
                    'device_serial' => $serialNumber,
                    'person_id' => $personId,
                ]);
                $solarHomeSystem = $this->solarHomeSystemService->create([
                    'serial_number' => $serialNumber,
                    'manufacturer_id' => $manufacturerId,
                    'appliance_id' => $applianceId,
                ]);
                $this->solarHomeSystemDeviceService->setAssigned($device);
                $this->solarHomeSystemDeviceService->setAssignee($solarHomeSystem);
                $this->solarHomeSystemDeviceService->assign();
                $this->deviceService->save($device);
                $solarHomeSystems[] = $solarHomeSystem->load(['manufacturer', 'appliance', 'device.person']);
            }

            return $solarHomeSystems;
        });

        return ApiResource::make($created);
    }

    public function search(Request $request): ApiResource {
        $term = $request->input('term', '');
        $paginate = (int) $request->input('per_page', 15);

        return ApiResource::make($this->solarHomeSystemService->search($term, $paginate));
    }

    public function show(int $id): ApiResource {
        return ApiResource::make($this->solarHomeSystemService->getById($id));
    }

    public function update(int $id, UpdateSolarHomeSystemRequest $request): ApiResource {
        $solarHomeSystem = $this->solarHomeSystemService->getById($id);

        $updated = $this->solarHomeSystemService->update($solarHomeSystem, [
            'manufacturer_id' => $request->integer('manufacturer_id'),
            'appliance_id' => $request->integer('appliance_id'),
        ]);

        return ApiResource::make($updated);
    }

    public function transactions(int $id): ApiResource {
        $shs = $this->solarHomeSystemService->getById($id);
        $paginate = request('paginate') ?? 15;

        return ApiResource::make($this->paymentHistoryService->getBySerialNumber($shs->serial_number, $paginate));
    }
}
