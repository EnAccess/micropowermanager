<?php

namespace App\Services;

use App\Models\ConnectionType;

class ConnectionTypeService extends BaseService
{
    public function __construct(private ConnectionType $connectionType)
    {
        parent::__construct([$connectionType]);
    }

    public function getConnectionTypes()
    {
        return $this->connectionType->newQuery()->get();

    }
}
