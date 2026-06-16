<?php

namespace App\Services;

use App\Exceptions\CompanyAlreadyExistsException;
use App\Models\Company;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * @implements IBaseService<Company>
 */
class CompanyService implements IBaseService {
    /** @use HasCrudOperations<Company> */
    use HasCrudOperations;

    public function __construct(
        private Company $company,
    ) {}

    protected function crudModel(): Company {
        return $this->company;
    }

    public function getByName(string $name): Company {
        return $this->company->where('name', $name)->firstOrFail();
    }

    public function getByDatabaseProxy(object $databaseProxy): Company {
        return $this->getById($databaseProxy->getCompanyId());
    }

    public function getById(int $id): Company {
        return $this->company->newQuery()->findOrFail($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Company {
        try {
            return $this->company->newQuery()->create($data);
        } catch (UniqueConstraintViolationException) {
            throw new CompanyAlreadyExistsException('Company already exists');
        }
    }
}
