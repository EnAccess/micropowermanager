<?php

namespace App\Services;

use App\Models\Token;

class TokenService implements IBaseService
{
    public function __construct(private Token $token)
    {
    }

    public function getById($id)
    {
        return $this->token->newQuery()->find($id);
    }

    public function create($data)
    {
        return $this->token->newQuery()->create($data);
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
        if ($limit) {
            return $this->token->newQuery()->paginate($limit);
        }
        return $this->token->newQuery()->get();
    }
}
