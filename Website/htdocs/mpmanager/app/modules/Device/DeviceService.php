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

        return $device->with(['person', 'device'])->first();
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }

    public function getAll($limit = null)
    {
        // TODO: Implement getAll() method.
    }

}