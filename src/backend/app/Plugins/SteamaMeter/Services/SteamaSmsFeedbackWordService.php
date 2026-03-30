<?php

namespace App\Plugins\SteamaMeter\Services;

use App\Plugins\SteamaMeter\Models\SteamaSmsFeedbackWord;
use Illuminate\Database\Eloquent\Collection;

class SteamaSmsFeedbackWordService {
    public function __construct(
        private SteamaSmsFeedbackWord $smsFeedbackWord,
    ) {}

    /**
     * @return Collection<int, SteamaSmsFeedbackWord>
     */
    public function getSmsFeedbackWords(): Collection {
        return $this->smsFeedbackWord->newQuery()->get();
    }

    public function createSmsFeedbackWord(): SteamaSmsFeedbackWord {
        return $this->smsFeedbackWord->newQuery()->firstOrCreate(['id' => 1], [
            'meter_balance' => null,
        ]);
    }

    /**
     * @param array<string, mixed> $smsFeedbackWordData
     */
    public function updateSmsFeedbackWord(SteamaSmsFeedbackWord $smsFeedbackWord, array $smsFeedbackWordData): SteamaSmsFeedbackWord {
        $smsFeedbackWord->update([
            'meter_balance' => $smsFeedbackWordData['meter_balance'],
        ]);

        return $smsFeedbackWord->fresh();
    }
}
