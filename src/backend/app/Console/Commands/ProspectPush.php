<?php

namespace App\Console\Commands;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProspectPush extends AbstractSharedCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prospect:push
                            {--file= : JSON file path containing data to push}
                            {--database : Load data from database}
                            {--limit= : Limit number of records when loading from database}
                            {--test : Mark data as test data}
                            {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push installation data to Prospect';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): void
    {
        try {
            $data = $this->prepareData();

            if(empty($data)) {
                $this->error("No data to push to Prospect.");
                return;
            }

            $payload = ['data' => $data];

            if ($this->option('dry-run')) {
                $this->info('DRY RUN - would send the following data:');
                $this->line(json_encode($payload, JSON_PRETTY_PRINT));
                return;
            }

            $this->info('Pushing ' . count($data) . ' installation(s) to Prospect...');

            $response = $this->sendToProspect($payload);

            if ($response->successful()) {
                $this->info('Successfully pushed data to Prospect');
                $this->line('Response: ' . $response->body());
                Log::info('Prospect push successful', [
                    'count' => count($data),
                    'response' => $response->json()
                ]);
            } else {
                $this->error("Failed to push data to Prospect");
                $this->error('Status: ' . $response->body());
                Log::error('Prospect push failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'data_count' => count($data)
                ]);
            }
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('Prospect push exception', [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function prepareData(): array {
        if ($this->option('file')) {
            return $this->loadDataFromFile();
        } elseif ($this->option('database')) {
            return $this->loadDataFromDatabase();
        } else {
            return $this->getDefaultTestData();
        }
    }

    private function loadDataFromFile(): array {
        $filePath = $this->option('file');

        if(!$filePath || !file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        $content = file_get_contents($filePath);
        $jsonData = json_decode($content, true);

        if(json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Invalid JSON in file: " . json_last_error_msg());
        }

        return isset($jsonData['data']) ? $jsonData['data'] : [$jsonData];
    }

    private function loadDataFromDatabase(): array {
        // TODO: Query your database for installation data

        $this->info("Loading installation data from database...");

        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $isTest = $this->option('test') ?? false;


        return [];
    }

    private function getDefaultTestData(): array {
        $isTest = $this->option('test') ?? true;

        return [
            [
                "customer_external_id" => "SMU 12 Chinsanka",
                "seller_agent_external_id" => "SMU 12 Chinsanka",
                "installer_agent_external_id" => "SMU 12 Chinsanka",
                "product_common_id" => "Verasol",
                "device_external_id" => "1",
                "parent_external_id" => "1",
                "account_external_id" => "1",
                "battery_capacity_wh" => 500,
                "usage_category" => "household",
                "usage_sub_category" => "farmer",
                "device_category" => "solar_home_system",
                "ac_input_source" => "generator, grid, wind turbine etc..",
                "dc_input_source" => "solar",
                "firmware_version" => "1.2-rc3",
                "manufacturer" => "HOP",
                "model" => "DTZ1737",
                "primary_use" => "cooking",
                "rated_power_w" => 30,
                "pv_power_w" => 50,
                "serial_number" => "A1233754345JL",
                "site_name" => "Hospital Name, Grid Name, etc",
                "payment_plan_amount_financed_principal" => 1500,
                "payment_plan_amount_financed_interest" => 1500,
                "payment_plan_amount_financed_total" => 1500,
                "payment_plan_amount_down_payment" => 1500,
                "payment_plan_cash_price" => 20000,
                "payment_plan_currency" => "ZMW",
                "payment_plan_installment_amount" => 25000,
                "payment_plan_number_of_installments" => 365,
                "payment_plan_installment_period_days" => 180,
                "payment_plan_days_financed" => 3650,
                "payment_plan_days_down_payment" => 30,
                "payment_plan_category" => "paygo",
                "purchase_date" => "2022-01-01",
                "installation_date" => "2022-01-01",
                "repossession_date" => "2022-01-01",
                "paid_off_date" => "2022-01-01",
                "repossession_category" => "swap",
                "write_off_date" => "2022-01-01",
                "write_off_reason" => "Return",
                "is_test" => $isTest,
                "latitude" => 37.775,
                "longitude" => -122.419,
                "country" => "UG",
                "location_area_1" => "Northern",
                "location_area_2" => "Agago",
                "location_area_3" => "Arum",
                "location_area_4" => "Alela",
                "location_area_5" => "Bila"
            ]
        ];
    }

    private function sendToProspect(array $payload): Response {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . env('PROSPECT_API_TOKEN'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(30)->post(env('PROSPECT_API_URL'), $payload);
    }
}
