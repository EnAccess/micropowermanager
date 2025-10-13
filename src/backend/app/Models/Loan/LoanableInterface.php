<?php

namespace App\Models\Loan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface LoanableInterface {
    /**
     * @return MorphMany<Loan, Model>
     */
    public function loans(): MorphMany;
}
