<?php

namespace Inensus\SparkMeter\Services;

use Inensus\SparkMeter\Models\SmSmsFeedbackWord;

class SmSmsFeedbackWordService {
    private SmSmsFeedbackWord $smsFeedbackWord;

    public function __construct(SmSmsFeedbackWord $smsFeedbackWord) {
        $this->smsFeedbackWord = $smsFeedbackWord;
    }

    public function getSmsFeedbackWords() {
        return $this->smsFeedbackWord->newQuery()->get();
    }

    public function createSmsFeedbackWord() {
        return $this->smsFeedbackWord->newQuery()->firstOrCreate(['id' => 1], [
            'meter_reset' => null,
            'meter_balance' => null,
        ]);
    }

    public function updateSmsFeedbackWord($smsFeedbackWord, array $smsFeedbackWordData) {
        $smsFeedbackWord->update([
            'meter_reset' => $smsFeedbackWordData['meter_reset'],
            'meter_balance' => $smsFeedbackWordData['meter_balance'],
        ]);

        return $smsFeedbackWord->fresh();
    }
}
