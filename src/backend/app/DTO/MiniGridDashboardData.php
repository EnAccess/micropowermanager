<?php

namespace App\DTO;

use App\Models\MiniGrid;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Collection;

/**
 * Data container for computed mini-grid dashboard fields.
 * This separates cached/computed data from the actual model to prevent
 * inconsistencies between cached and fresh model instances.
 */
class MiniGridDashboardData {
    /**
     * @param array<float>                 $soldEnergy
     * @param Collection<int, Transaction> $transactions
     * @param array<string, mixed>         $period
     * @param array<string, mixed>         $tickets
     * @param array<string, mixed>         $revenueList
     */
    public function __construct(
        public MiniGrid $miniGrid,
        public array $soldEnergy = [],
        public Collection $transactions = new Collection(),
        public array $period = [],
        public array $tickets = [],
        public array $revenueList = [],
    ) {}

    /**
     * Convert to array for API responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array {
        return [
            ...$this->miniGrid->toArray(),
            'soldEnergy' => $this->soldEnergy,
            'transactions' => $this->transactions->toArray(),
            'period' => $this->period,
            'tickets' => $this->tickets,
            'revenueList' => $this->revenueList,
        ];
    }
}
