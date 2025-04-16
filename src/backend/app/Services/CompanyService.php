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

    public function getByName($name): Company {
        return $this->company->where('name', $name)->firstOrFail();
    }

    public function getByDatabaseProxy($databaseProxy): Company {
        return $this->getById($databaseProxy->getCompanyId());
    }

    public function getById($id): Company {
        $result = $this->company->newQuery()->findOrFail($id);
        if (isset($result->protected_page_password)) {
            if (str_starts_with($result->protected_page_password, 'eyJ')) {
                $result->protected_page_password = Crypt::decrypt($result->protected_page_password);
            }
        }

        return $result;
    }

    public function create($data): Company {
        return $this->company->newQuery()->create($data);
    }

    public function update($model, array $data): Company {
        $model->update($data);

        return $model;
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    public function getAll(?int $limit = null): Collection {
        return $this->company->newQuery()->get();
    }
}
