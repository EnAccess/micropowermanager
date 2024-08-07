<?php

namespace Inensus\ViberMessaging\Services;

use App\Services\IBaseService;
use Inensus\ViberMessaging\Models\ViberMessage;

class ViberMessageService implements IBaseService
{
    public function __construct(private ViberMessage $viberMessage)
    {
    }

    public function getById(int $id): Model
    {
        throw new \Exception('Method getById() not yet implemented.');
    }

    public function create($data)
    {
        return $this->viberMessage->newQuery()->create($data);
    }

    public function update($model, array $data): Model
    {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool
    {
        throw new \Exception('Method delete() not yet implemented.');
    }

    public function getAll($limit = null)
    {
        // TODO: Implement getAll() method.
    }
}
