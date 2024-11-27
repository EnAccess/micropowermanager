<?php

namespace Inensus\SteamaMeter\Services;

use Inensus\SteamaMeter\Models\SteamaSmsFeedbackWord;

class SteamaSmsFeedbackWordService {
    private $smsFeedbackWord;

    public function __construct(SteamaSmsFeedbackWord $smsFeedbackWord) {
        $this->smsFeedbackWord = $smsFeedbackWord;
    }

    public function getSmsFeedbackWords() {
        return $this->smsFeedbackWord->newQuery()->get();
    }

    public function createSmsFeedbackWord() {
        return $this->smsFeedbackWord->newQuery()->firstOrCreate(['id' => 1], [
            'meter_balance' => null,
        ]);
    }

    public function updateSmsFeedbackWord($smsFeedbackWord, $smsFeedbackWordData) {
        $smsFeedbackWord->update([
            'meter_balance' => $smsFeedbackWordData['meter_balance'],
        ]);

        return $smsFeedbackWord->fresh();
    }
}
