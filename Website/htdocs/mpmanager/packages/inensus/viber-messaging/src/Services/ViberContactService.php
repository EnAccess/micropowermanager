<?php

namespace Inensus\ViberMessaging\Services;

use App\Models\Person\Person;
use App\Services\IBaseService;
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
        // TODO: Implement getById() method.
    }

    public function create($data)
    {
        // TODO: Implement create() method.
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
                $q->whereHas('addresses',
                    function ($q) use ($receiver) {
                        $q->where('phone', 'LIKE', '%' . $receiver . '%');
                    }
                );
        })->first();
    }
}