<?php

namespace Database\Seeders;

use App\Models\Sms;
use App\Models\SmsAndroidSetting;
use App\Models\SmsResendInformationKey;
use App\Models\SmsVariableDefaultValue;
use App\Services\CompanyService;
use Illuminate\Database\Seeder;

class SmsSeeder extends Seeder {
    public function __construct(
        private CompanyService $companyService,
    ) {
        $this->companyService->buildDatabaseConnectionDemoCompany();
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        SmsVariableDefaultValue::factory()->count(12)->create();
        SmsResendInformationKey::factory()->create();

        SmsAndroidSetting::factory()->count(2)->create();
        Sms::factory()->count(2000)->create();
    }
}
