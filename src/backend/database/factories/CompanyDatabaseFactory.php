<?php

namespace Database\Factories;

use App\Models\CompanyDatabase;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyDatabaseFactory extends Factory {
    protected $model = CompanyDatabase::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'company_id' => 1,
            'database_name' => 'test_company_db',
        ];
    }
}
