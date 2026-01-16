<?php

namespace App\Plugins\SparkMeter\Services;

use App\Plugins\SparkMeter\Models\SmSmsFeedbackWord;
use Illuminate\Database\Eloquent\Collection;

class SmSmsFeedbackWordService {
    public function __construct(
        private SmSmsFeedbackWord $smsFeedbackWord,
    ) {}

    /**
     * @return Collection<int, SmSmsFeedbackWord>
     */
    public function getSmsFeedbackWords(): Collection {
        return $this->smsFeedbackWord->newQuery()->get();
    }

    public function createSmsFeedbackWord(): SmSmsFeedbackWord {
        return $this->smsFeedbackWord->newQuery()->firstOrCreate(['id' => 1], [
            'meter_reset' => null,
            'meter_balance' => null,
        ]);
    }

    /**
     * @param array<string, mixed> $smsFeedbackWordData
     */
    public function updateSmsFeedbackWord(SmSmsFeedbackWord $smsFeedbackWord, array $smsFeedbackWordData): ?SmSmsFeedbackWord {
        $smsFeedbackWord->update([
            'meter_reset' => $smsFeedbackWordData['meter_reset'],
            'meter_balance' => $smsFeedbackWordData['meter_balance'],
        ]);

        return $smsFeedbackWord->fresh();
    }
}
