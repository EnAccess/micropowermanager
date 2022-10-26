<?php

namespace App\Services;

use App\Models\Company;

class CompanyService implements IBaseService
{
    public function __construct(private Company $company)
    {
    }

    public function getById($id)
    {
        // TODO: Implement getById() method.
    }

    public function create($data): Company
    {
        /** @var Company $company */
        $company =  $this->company->newQuery()->create($data);

        return $company;
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
