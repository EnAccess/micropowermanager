<?php

namespace App\Services;

use App\Models\Token;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;
use Illuminate\Database\Eloquent\Collection;

/**
 * @implements IBaseService<Token>
 */
class TokenService implements IBaseService {
    /** @use HasCrudOperations<Token> */
    use HasCrudOperations;

    public function __construct(
        private Token $token,
    ) {}

    protected function crudModel(): Token {
        return $this->token;
    }

    /**
     * @return Collection<int, Token>
     */
    public function getTokensInRange(?string $startDate, ?string $endDate): Collection {
        return $this->token->newQuery()->with(['transaction.device'])
            ->whereHas('transaction.device', function ($query) {
                $query->where('device_type', 'meter');
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
    }
}
