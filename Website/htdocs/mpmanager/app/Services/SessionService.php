<?php

namespace App\Services;

use App\Models\BaseModel;
use App\Models\User;
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

    public function getAuthenticatedUserDatabaseName(): string
    {
        if (auth('api')->user()) {
            return auth('api')->user()->company->database->database_name;
        }
        return 'test_company_db';
    }
}