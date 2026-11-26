<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Frozen copy of the former central mpm_plugins ids and names, so the backfill stays
     * fixed however the plugin catalog changes later.
     *
     * @var array<int, array{slug: string, names: list<string>}>
     */
    private const PLUGINS = [
        1 => ['slug' => 'spark_meter', 'names' => ['SparkMeter']],
        2 => ['slug' => 'steama_meter', 'names' => ['SteamaMeter']],
        3 => ['slug' => 'calin_meter', 'names' => ['CalinMeter']],
        4 => ['slug' => 'calin_smart_meter', 'names' => ['CalinSmartMeter']],
        5 => ['slug' => 'kelin_meter', 'names' => ['KelinMeter']],
        6 => ['slug' => 'stron_meter', 'names' => ['StronMeter']],
        7 => ['slug' => 'swifta_payment_provider', 'names' => ['SwiftaPayment']],
        8 => ['slug' => 'mesomb_payment_provider', 'names' => ['MesombPayment']],
        9 => ['slug' => 'bulk_registration', 'names' => ['BulkRegistration']],
        10 => ['slug' => 'viber_messaging', 'names' => ['ViberMessaging']],
        11 => ['slug' => 'wave_money_payment_provider', 'names' => ['WaveMoneyPayment']],
        12 => ['slug' => 'micro_star_meter', 'names' => ['MicroStarMeter']],
        13 => ['slug' => 'sun_king_shs', 'names' => ['SunKingSHS']],
        14 => ['slug' => 'gome_long_meter', 'names' => ['GomeLongMeter']],
        15 => ['slug' => 'wavecom_payment_provider', 'names' => ['WavecomPayment']],
        16 => ['slug' => 'daly_bms', 'names' => ['DalyBms']],
        17 => ['slug' => 'angaza_shs', 'names' => ['AngazaSHS']],
        18 => ['slug' => 'africas_talking', 'names' => ['AfricasTalking']],
        19 => ['slug' => 'vodacom_mz_payment_provider', 'names' => ['VodacomMzPaymentProvider', 'VodacomMobileMoney']],
        20 => ['slug' => 'chint_meter', 'names' => ['ChintMeter']],
        21 => ['slug' => 'demo_meter_manufacturer', 'names' => ['DemoMeterManufacturer']],
        22 => ['slug' => 'demo_shs_manufacturer', 'names' => ['DemoShsManufacturer']],
        23 => ['slug' => 'odyssey_data_export', 'names' => ['OdysseyDataExport']],
        24 => ['slug' => 'prospect', 'names' => ['Prospect']],
        25 => ['slug' => 'paystack_payment_provider', 'names' => ['PaystackPaymentProvider']],
        26 => ['slug' => 'textbee_sms_gateway', 'names' => ['TextbeeSmsGateway']],
        27 => ['slug' => 'ecreee_e_tender', 'names' => ['Ecreee E-Tender']],
        28 => ['slug' => 'spark_shs', 'names' => ['SparkShs']],
        29 => ['slug' => 'sms_transaction_parser', 'names' => ['SmsTransactionParser']],
        30 => ['slug' => 'pesapal_payment_provider', 'names' => ['PesapalPaymentProvider']],
        31 => ['slug' => 'safaricom_ke_payment_provider', 'names' => ['SafaricomKePaymentProvider']],
        32 => ['slug' => 'flutterwave_payment_provider', 'names' => ['FlutterwavePaymentProvider']],
        33 => ['slug' => 'sts_meter_vend', 'names' => ['StsMeterVend']],
    ];

    /**
     * Columns holding a plugin id, keyed by table, mapped to the column replacing them
     * and the plugin ids the column may hold, null for any plugin.
     *
     * @var array<string, array{0: string, 1: string, 2: list<int>|null}>
     */
    private const PLUGIN_ID_COLUMNS = [
        'plugins' => ['mpm_plugin_id', 'mpm_plugin', null],
        'devices' => ['vending_plugin_id', 'vending_plugin', null],
        'main_settings' => ['sms_gateway_id', 'sms_gateway', self::SMS_GATEWAY_PLUGIN_IDS],
        'sms' => ['gateway_id', 'gateway', self::SMS_GATEWAY_PLUGIN_IDS],
    ];

    /**
     * Viber, Africa's Talking and TextBee. Other ids in the SMS gateway columns, e.g. 2 for
     * the removed Android gateway, are not plugin ids even where they collide with one.
     */
    private const SMS_GATEWAY_PLUGIN_IDS = [10, 18, 26];

    public function up(): void {
        $tenant = DB::connection('tenant');
        $schema = Schema::connection('tenant');

        foreach (self::PLUGIN_ID_COLUMNS as $table => [$idColumn, $slugColumn, $allowedIds]) {
            $schema->table($table, function (Blueprint $blueprint) use ($idColumn, $slugColumn, $table) {
                $column = $blueprint->string($slugColumn, 64)->nullable()->after($idColumn);
                if ($table === 'devices') {
                    $column->index();
                }
            });

            // Ids that do not name a plugin this column may hold become null.
            foreach (self::PLUGINS as $id => $plugin) {
                if ($allowedIds !== null && !in_array($id, $allowedIds, true)) {
                    continue;
                }
                $tenant->table($table)->where($idColumn, $id)->update([$slugColumn => $plugin['slug']]);
            }

            $schema->table($table, function (Blueprint $blueprint) use ($idColumn) {
                $blueprint->dropColumn($idColumn);
            });
        }

        // A tenant plugin row without a known plugin can never be resolved again.
        $tenant->table('plugins')->whereNull('mpm_plugin')->delete();
        $schema->table('plugins', function (Blueprint $blueprint) {
            $blueprint->string('mpm_plugin', 64)->nullable(false)->change();
        });

        foreach (self::PLUGINS as $plugin) {
            $tenant->table('registration_tail')->whereIn('component', $plugin['names'])->update(['component' => $plugin['slug']]);
        }
    }

    public function down(): void {
        $tenant = DB::connection('tenant');
        $schema = Schema::connection('tenant');

        foreach (self::PLUGINS as $plugin) {
            $tenant->table('registration_tail')->where('component', $plugin['slug'])->update(['component' => $plugin['names'][0]]);
        }

        foreach (self::PLUGIN_ID_COLUMNS as $table => [$idColumn, $slugColumn]) {
            $schema->table($table, function (Blueprint $blueprint) use ($idColumn, $slugColumn, $table) {
                $column = $blueprint->unsignedInteger($idColumn)->nullable()->after($slugColumn);
                if ($table === 'devices') {
                    $column->index();
                }
            });

            foreach (self::PLUGINS as $id => $plugin) {
                $tenant->table($table)->where($slugColumn, $plugin['slug'])->update([$idColumn => $id]);
            }

            $schema->table($table, function (Blueprint $blueprint) use ($slugColumn) {
                $blueprint->dropColumn($slugColumn);
            });
        }
    }
};
