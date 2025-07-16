<?php

namespace App\Services;

use App\Models\MpmPlugin;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<MpmPlugin>
 */
class MpmPluginService implements IBaseService {
    public function __construct(
        private MpmPlugin $mpmPlugin,
    ) {}

    /**
     * @param int|string $id
     */
    public function getById($id): MpmPlugin {
        /** @var MpmPlugin $result */
        $result = $this->mpmPlugin->newQuery()->findOrFail($id);

        return $result;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): MpmPlugin {
        throw new \Exception('Method create() not yet implemented.');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update($model, array $data): MpmPlugin {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @return Collection<int, MpmPlugin>|LengthAwarePaginator<MpmPlugin>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit) {
            return $this->mpmPlugin->newQuery()
                ->paginate($limit);
        }

        return $this->mpmPlugin->newQuery()
            ->get();
    }
}
