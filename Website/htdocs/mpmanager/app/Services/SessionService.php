<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SessionService
{
    private BaseModel|Model $model;

    public function setModel(BaseModel|Model $model): void
    {
        $this->model = $model;
        $databaseName = $model->getConnectionName();
        if (!$this->checkDatabaseName($databaseName)) {
            $this->model->setConnection($this->getAuthenticatedUserDatabaseName());
        }
    }

    private function checkDatabaseName($databaseName): bool
    {
        return $this->getAuthenticatedUserDatabaseName() == $databaseName;
    }

    public function getAuthenticatedUserDatabaseName(): string
    {
        return config()->get('database.connections.shard.database');
    }
}
