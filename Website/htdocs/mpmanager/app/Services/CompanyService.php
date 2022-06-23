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

    public function create($companyData)
    {
        return $this->company->newQuery()->create($companyData);
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