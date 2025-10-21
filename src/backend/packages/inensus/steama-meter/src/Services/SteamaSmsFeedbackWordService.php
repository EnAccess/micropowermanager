<?php

namespace Inensus\SteamaMeter\Services;

use Inensus\SteamaMeter\Models\SteamaSmsFeedbackWord;

class SteamaSmsFeedbackWordService {
    public function __construct(private SteamaSmsFeedbackWord $smsFeedbackWord) {}

    public function getSmsFeedbackWords() {
        return $this->smsFeedbackWord->newQuery()->get();
    }

    public function createSmsFeedbackWord() {
        return $this->smsFeedbackWord->newQuery()->firstOrCreate(['id' => 1], [
            'meter_balance' => null,
        ]);
    }

    public function updateSmsFeedbackWord($smsFeedbackWord, array $smsFeedbackWordData) {
        $smsFeedbackWord->update([
            'meter_balance' => $smsFeedbackWordData['meter_balance'],
        ]);

        return $smsFeedbackWord->fresh();
    }
}
