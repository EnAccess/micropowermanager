<?php

namespace App\Services;

use App\Models\Company;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Crypt;

/**
 * @implements IBaseService<Company>
 */
class CompanyService implements IBaseService {
    public function __construct(
        private Company $company,
    ) {}

    public function getByName(string $name): Company {
        return $this->company->where('name', $name)->firstOrFail();
    }

    public function getByDatabaseProxy(object $databaseProxy): Company {
        return $this->getById($databaseProxy->getCompanyId());
    }

    public function getById(int $id): Company {
        $result = $this->company->newQuery()->findOrFail($id);
        if (isset($result->protected_page_password)) {
            if (str_starts_with($result->protected_page_password, 'eyJ')) {
                $result->protected_page_password = Crypt::decrypt($result->protected_page_password);
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Company {
        $company = $this->company->newQuery()->create($data);

        return $company;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update($model, array $data): Company {
        $model->update($data);

        return $model;
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @return Collection<int, Company>
     */
    public function getAll(?int $limit = null): Collection {
        return $this->company->newQuery()->get();
    }
}
