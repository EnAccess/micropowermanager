<?php

namespace App\Services;

use App\Models\Company;

class CompanyService implements IBaseService
{
    public function __construct(private Company $company)
    {
    }

    public function getByName($name): Company
    {
        return $this->company->where('name', $name)->firstOrFail();
    }

    public function getByDatabaseProxy($databaseProxy): Company
    {
        return $this->getById($databaseProxy->getCompanyId());
    }

    public function getById($id): Company
    {
        /** @var Company $result */
        $result = $this->company->newQuery()->findOrFail($id);

        return $result;
    }

    public function create($data): Company
    {
        /** @var Company $company */
        $company = $this->company->newQuery()->create($data);

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
