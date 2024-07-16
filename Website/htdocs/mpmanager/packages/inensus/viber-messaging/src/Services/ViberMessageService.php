<?php

namespace Inensus\ViberMessaging\Services;

use App\Services\IBaseService;
use Inensus\ViberMessaging\Models\ViberMessage;

class ViberMessageService implements IBaseService
{
    public function __construct(private ViberMessage $viberMessage)
    {
    }

    public function getById($id)
    {
        // TODO: Implement getById() method.
    }

    public function create($data)
    {
        return $this->viberMessage->newQuery()->create($data);
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
}
