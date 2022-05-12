<?php

namespace App\Services;


abstract class BaseService
{

    public function __construct(array $models)
    {
        $this->setModels($models);
    }

    protected function setModels(array $models)
    {
        foreach ($models as $model) {
            $sessionService = app()->make(SessionService::class);
            $sessionService->setModel($model);
        }
    }


}