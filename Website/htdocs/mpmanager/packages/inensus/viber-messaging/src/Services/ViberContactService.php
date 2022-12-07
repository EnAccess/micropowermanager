<?php

namespace Inensus\ViberMessaging\Services;

use App\Models\Person\Person;
use App\Services\IBaseService;
use Illuminate\Support\Facades\Log;
use Inensus\ViberMessaging\Models\ViberContact;

class ViberContactService implements IBaseService
{

    public function __construct(private ViberContact $viberContact, private Person $person)
    {
    }

    public function createContact($personId, $viberId)
    {
        return $this->viberContact->newQuery()->firstOrCreate(['person_id' => $personId], [
            'viber_id' => $viberId,
        ]);
    }

    public function getById($id)
    {
        return $this->viberContact->newQuery()->find($id);
    }

    public function create($data)
    {
        return $this->viberContact->newQuery()->create($data);
    }

    public function update($model, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }

    public function getAll($limit = null)
    {
        // TODO: Implement getAll() method.
    }

    public function getByReceiverPhoneNumber($receiver)
    {

        return $this->viberContact->newQuery()
            ->whereHas('mpmPerson', function ($q) use ($receiver) {
                $q->whereHas('addresses', static function ($q) use ($receiver) {
                    $q->where('phone', $receiver)->orWhere('phone', ltrim($receiver, '+'));
                });
            })->first();

    }


    public function getByRegisteredMeterSerialNumber($meterSerialNumber)
    {
        return $this->viberContact->newQuery()->where('registered_meter_serial_number', $meterSerialNumber)->first();
    }
    public function getByViberId($viberId)
    {
        return $this->viberContact->newQuery()->where('viber_id', $viberId)->first();
    }

}