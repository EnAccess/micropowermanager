<?php

namespace MPM\Device;

use App\Models\Device;
use App\Services\IAssociative;
use App\Services\IBaseService;

class DeviceService implements IBaseService, IAssociative
{

    public function __construct(
        private Device $device
    ) {
    }

    public function make($deviceData)
    {
        return $this->device->newQuery()->create([
            'person_id' => $deviceData['person_id'],
            'device_serial' => $deviceData['device_serial'],
        ]);
    }
    public function getBySerialNumber($serialNumber)
    {
        return $this->device->newQuery()
            ->with(['address.geo','device.manufacturer','person'])
            ->where('device_serial', $serialNumber)
            ->first();
    }
    public function save($device)
    {
        return $device->save();
    }

    public function getById($id)
    {
        // TODO: Implement getById() method.
    }

    public function create($data)
    {
        // TODO: Implement getById() method.
    }

    public function update($device, $deviceData)
    {
        $device->update($deviceData);
        $device->fresh();

        return $device;
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }

    public function getAll($limit = null)
    {
        if($limit){
            return $this->device->newQuery()->with(['person','device'])->paginate($limit);
        }
        return $this->device->newQuery()->with(['person','device'])->get();
    }

}
