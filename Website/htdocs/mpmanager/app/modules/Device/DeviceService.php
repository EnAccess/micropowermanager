<?php

namespace MPM\Device;

use App\Models\Device;
use App\Services\IAssociative;
use App\Services\IBaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DeviceService implements IBaseService, IAssociative
{
    public function __construct(
        private Device $device
    ) {
    }

    public function make($deviceData): Device
    {
        /** @var Device $result */
        $result = $this->device->newQuery()->create([
            'person_id' => $deviceData['person_id'],
            'device_serial' => $deviceData['device_serial'],
        ]);

        return $result;
    }

    public function getBySerialNumber($serialNumber): ?Device
    {
        /** @var Device|null $result */
        $result = $this->device->newQuery()
            ->with(['address.geo', 'device.manufacturer', 'person'])
            ->where('device_serial', $serialNumber)
            ->first();

        return $result;
    }

    public function save($device)
    {
        return $device->save();
    }

    public function getById($id)
    {
        throw new \Exception('Not implemented');
    }

    public function create($data)
    {
        throw new \Exception('Not implemented');
    }

    public function update($device, $deviceData)
    {
        $device->update($deviceData);
        $device->fresh();

        return $device;
    }

    public function delete($model)
    {
        throw new \Exception('Not implemented');
    }

    public function getAll($limit = null): Collection|LengthAwarePaginator
    {
        if ($limit) {
            return $this->device->newQuery()->with(['person', 'device'])->paginate($limit);
        }

        return $this->device->newQuery()->with(['person', 'device'])->get();
    }
}
