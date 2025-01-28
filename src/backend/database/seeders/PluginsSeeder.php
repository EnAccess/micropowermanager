<?php

namespace Database\Seeders;

use App\Models\Plugins;
use Database\Factories\CalinCredentialFactory;
use Database\Factories\CsvDataFactory;
use Database\Factories\WaveMoneyCredentialFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class PluginsSeeder extends Seeder {
    public function __construct(
        private DatabaseProxyManagerService $databaseProxyManagerService,
    ) {
        $this->databaseProxyManagerService->buildDatabaseConnectionDummyCompany();
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        $this->generatePlugins();
    }

    private function generatePlugins() {
        Plugins::factory()->count(10)->create();
        $this->generateBulkRegistrationCsvData();
        $this->generateCalinCredential();
        $this->generateWaveMoneyCredential();
    }

    private function generateBulkRegistrationCsvData() {
        $factory = new CsvDataFactory();
        $dummyDataPath = database_path('dummyData');

        // Get the contents of the CSV files as arrays
        $csvFiles = [
            $dummyDataPath.'/bulk-registration-template-1.hex',
            $dummyDataPath.'/bulk-registration-template-2.hex',
        ];
        $csvData = [];

        foreach ($csvFiles as $csvFile) {
            if (file_exists($csvFile)) {
                // Read the entire CSV file content as a string
                $csvData[] = file_get_contents($csvFile);
            } else {
                throw new \Exception('CSV file not found: '.$csvFile);
            }
        }

        foreach ($csvData as $data) {
            $factory->create([
                'csv_data' => DB::raw("X'{$data}'"),
            ]);
        }
    }

    private function generateCalinCredential($count = 2) {
        (new CalinCredentialFactory())->count($count)->create();
    }

    private function generateWaveMoneyCredential($count = 2) {
        (new WaveMoneyCredentialFactory())->count($count)->create();
    }
}
