<?php

namespace App\Services\Interfaces;

use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface IAgentTransactionService
{
    /**
     * @return Collection<int, Transaction>|LengthAwarePaginator<Transaction>
     */
    public function getAll(?int $limit = null, ?int $agentId = null, bool $forApp = false): Collection|LengthAwarePaginator;

    /**
     * @return Collection<int, Transaction>|LengthAwarePaginator<Transaction>
     */
    public function getByCustomerId(int $agentId, ?int $customerId = null): Collection|LengthAwarePaginator;

    public function getById(int $id): AgentTransaction;

    /**
     * @param array<string, mixed> $transactionData
     */
    public function create(array $transactionData): AgentTransaction;

    /**
     * @param array<string, mixed> $data
     */
    public function update(AgentTransaction $model, array $data): AgentTransaction;

    public function delete(AgentTransaction $model): ?bool;
}
