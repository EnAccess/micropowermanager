<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::dropIfExists('mpm_plugins');
    }

    /**
     * Restores the table with each plugin's identity, but not its description, which now
     * lives only in App\Enums\MpmPlugin.
     */
    public function down(): void {
        Schema::create('mpm_plugins', function (Blueprint $table) {
            $table->id();
            $table->enum('usage_type', ['mini-grid', 'shs', 'e-bike', 'general'])->default('general');
            $table->string('name');
            $table->text('description');
            $table->string('installation_command')->nullable();
            $table->string('root_class')->nullable();
            $table->timestamps();
        });

        $plugins = [
            1 => ['SparkMeter', 'spark-meter', 'mini-grid'],
            2 => ['SteamaMeter', 'steama-meter', 'mini-grid'],
            3 => ['CalinMeter', 'calin-meter', 'mini-grid'],
            4 => ['CalinSmartMeter', 'calin-smart-meter', 'mini-grid'],
            5 => ['KelinMeter', 'kelin-meter', 'mini-grid'],
            6 => ['StronMeter', 'stron-meter', 'mini-grid'],
            7 => ['SwiftaPayment', 'swifta-payment-provider', 'general'],
            8 => ['MesombPayment', 'mesomb-payment-provider', 'general'],
            9 => ['BulkRegistration', 'bulk-registration', 'general'],
            10 => ['ViberMessaging', 'viber-messaging', 'general'],
            11 => ['WaveMoneyPayment', 'wave-money-payment-provider', 'general'],
            12 => ['MicroStarMeter', 'micro-star-meter', 'mini-grid'],
            13 => ['SunKingSHS', 'sun-king-shs', 'shs'],
            14 => ['GomeLongMeter', 'gome-long-meter', 'mini-grid'],
            15 => ['WavecomPayment', 'wavecom-payment-provider', 'general'],
            16 => ['DalyBms', 'daly-bms', 'e-bike'],
            17 => ['AngazaSHS', 'angaza-shs', 'shs'],
            18 => ['AfricasTalking', 'africas-talking', 'general'],
            19 => ['VodacomMzPaymentProvider', 'vodacom-mz-payment-provider', 'general'],
            20 => ['ChintMeter', 'chint-meter', 'mini-grid'],
            21 => ['DemoMeterManufacturer', 'demo-meter-manufacturer', 'general'],
            22 => ['DemoShsManufacturer', 'demo-shs-manufacturer', 'general'],
            23 => ['OdysseyDataExport', 'odyssey-data-export', 'general'],
            24 => ['Prospect', 'prospect', 'general'],
            25 => ['PaystackPaymentProvider', 'paystack-payment-provider', 'general'],
            26 => ['TextbeeSmsGateway', 'textbee-sms-gateway', 'general'],
            27 => ['Ecreee E-Tender', 'ecreee-e-tender', 'general'],
            28 => ['SparkShs', 'spark-shs', 'general'],
            29 => ['SmsTransactionParser', 'sms-transaction-parser', 'general'],
            30 => ['PesapalPaymentProvider', 'pesapal-payment-provider', 'general'],
            31 => ['SafaricomKePaymentProvider', 'safaricom-ke-payment-provider', 'general'],
            32 => ['FlutterwavePaymentProvider', 'flutterwave-payment-provider', 'general'],
            33 => ['StsMeterVend', 'sts-meter-vend', 'general'],
        ];

        DB::table('mpm_plugins')->insert(array_map(
            fn (int $id, array $plugin): array => [
                'id' => $id,
                'usage_type' => $plugin[2],
                'name' => $plugin[0],
                'description' => '',
                'installation_command' => "{$plugin[1]}:install",
                'root_class' => $plugin[0],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            array_keys($plugins),
            $plugins,
        ));
    }
};
