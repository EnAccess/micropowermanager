<?php

namespace App\Services;

use App\Models\MainSettings;
use App\Models\ProtectedPage;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;

/**
 * @implements IBaseService<ProtectedPage>
 */
class ProtectedPageService implements IBaseService {
    public function __construct(private ProtectedPage $protectedPage) {}

    public function compareProtectedPagePassword(MainSettings $mainSettings, string $password): bool {
        return Crypt::decrypt($mainSettings->protected_page_password) === $password;
    }

    public function getById(int $id): ?ProtectedPage {
        return $this->protectedPage->find($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): ProtectedPage {
        return $this->protectedPage->create($data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Model $model, array $data): ProtectedPage {
        /* @var ProtectedPage $model */
        $model->update($data);

        return $model;
    }

    public function delete(Model $model): ?bool {
        return $model->delete();
    }

    /**
     * @return Collection<int, ProtectedPage>|LengthAwarePaginator<ProtectedPage>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        return $this->protectedPage->all();
    }
}
