<?php

namespace App\Services;

use App\Models\Token;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<Token>
 */
class TokenService implements IBaseService {
    public function __construct(
        private Token $token,
    ) {}

    public function getById(int $id): Token {
        return $this->token->newQuery()->find($id);
    }

    public function create(array $data): Token {
        return $this->token->newQuery()->create($data);
    }

    public function update($model, array $data): Token {
        throw new \Exception('Method update() not yet implemented.');

        return new Token();
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit) {
            return $this->token->newQuery()->paginate($limit);
        }

        return $this->token->newQuery()->get();
    }
}
