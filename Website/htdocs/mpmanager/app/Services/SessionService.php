<?php

namespace App\Services;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;

class SessionService
{
    private BaseModel $model;
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
    private function getAuthenticatedUserDatabaseName():string
    {
        return auth('api')->user()->company->database->database_name;
    }
}