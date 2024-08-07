<?php

namespace App\Services;

use App\Models\Company;
use App\Services\Interfaces\IBaseService;

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

    public function update($model, array $data): Company
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
