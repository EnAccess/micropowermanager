<?php

namespace App\Models\Loan;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface LoanableInterface {
    /**
     * @return MorphMany<Loan, \Illuminate\Database\Eloquent\Model>
     */
    public function loans(): MorphMany;
}
