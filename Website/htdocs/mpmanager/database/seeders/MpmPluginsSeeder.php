<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MpmPluginsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::connection('micro_power_manager')->table('mpm_plugins')->insert(array(
                [
                    'name' => 'SparkMeter',
                    'description' => 'This plugin uses KoiosAPI for the main authentication. After it got authenticated it uses the ThunderCloud API for basic CRUD operations. You need to enter the ThunderCloud Token on the site',
                    'tail_tag' => 'Spark Meter',
                    'installation_command' => 'spark-meter:install',
                    'root_class' => 'SparkMeter',
                    'usage_type' => 'mini-grid'
                ],
                [
                    'name' => 'SteamaMeter',
                    'description' => 'This plugin integrates Steamaco meters to Micropowermanager. It uses the same  credentials as ui.steama.co for authentication. After it got authenticated, the plugin synchronizes Site, Customer ..',
                    'tail_tag' => 'Steamaco Meter',
                    'installation_command' => 'steama-meter:install',
                    'root_class' => 'SteamaMeter',
                    'usage_type' => 'mini-grid'
                ],
                [
                    'name' => 'CalinMeter',
                    'description' => 'This plugin integrates Calin meters to Micropowermanager. It uses user_id & api_key for creating tokens for energy.',
                    'tail_tag' => 'Calin Meter',
                    'installation_command' => 'calin-meter:install',
                    'root_class' => 'CalinMeter',
                    'usage_type' => 'mini-grid'
                ],
                [
                    'name' => 'CalinSmartMeter',
                    'description' => 'This plugin integrates Calin meters to Micropowermanager. It uses company_name, user_name, password and password_vend for creating tokens for energy.',
                    'tail_tag' => 'CalinSmart Meter',
                    'installation_command' => 'calin-smart-meter:install',
                    'root_class' => 'CalinSmartMeter',
                    'usage_type' => 'mini-grid'
                ],
                [
                    'name' => 'KelinMeter',
                    'description' => 'This plugin integrates Kelim meters to Micropowermanager. It uses username & password for creating tokens for energy.',
                    'tail_tag' => 'Kelin Meter',
                    'installation_command' => 'kelin-meter:install',
                    'root_class' => 'KelinMeter',
                    'usage_type' => 'mini-grid'
                ],
                [
                    'name' => 'StronMeter',
                    'description' => 'This plugin integrates Stron meters to Micropowermanager. It uses the api login credentials for authentication.',
                    'tail_tag' => 'Stron Meter',
                    'installation_command' => 'stron-meter:installl',
                    'root_class' => 'StronMeter',
                    'usage_type' => 'mini-grid'
                ],
                [
                    'name' => 'SwiftaPayment',
                    'description' => 'This plugin developed for getting Swifta payments into MicroPowerManager.',
                    'tail_tag' => null,
                    'installation_command' => 'swifta-payment-provider:install',
                    'root_class' => 'SwiftaPaymentProvider',
                    'usage_type' => 'general'
                ],
                [
                    'name' => 'MesombPayment',
                    'description' => 'This plugin developed for getting MeSomb payments into MicroPowerManager.',
                    'tail_tag' => null,
                    'installation_command' => 'mesomb-payment-provider:install',
                    'root_class' => 'MesombPaymentProvider',
                    'usage_type' => 'general'
                ],
                [
                    'name' => 'BulkRegistration',
                    'description' => 'This plugin provides bulk registration of the company\'s existing records. NOTE: Please do not use this plugin to register your Spark & Stemaco meter records. These records will be synchronized automatically once you configure your credential settings for these plugins.',
                    'tail_tag' => null,
                    'installation_command' => 'bulk-registration:install',
                    'root_class' => 'BulkRegistration',
                    'usage_type' => 'general'
                ],
                [
                    'name' => 'ViberMessaging',
                    'description' => 'This plugin developed for the communication with customers throughout Viber messages.',
                    'tail_tag' => 'Viber Messaging',
                    'installation_command' => 'viber-messaging:install',
                    'root_class' => 'ViberMessaging',
                    'usage_type' => 'general'
                ],
                [
                    'name' => 'WaveMoneyPayment',
                    'description' => 'This plugin developed for getting WaveMoney payments into MicroPowerManager.',
                    'tail_tag' => 'WaveMoney',
                    'installation_command' => 'wave-money-payment-provider:install',
                    'root_class' => 'WaveMoneyPaymentProvider',
                    'usage_type' => 'general'
                ],
                [
                    'name' => 'MicroStarMeter',
                    'description' => 'This plugin integrates MicroStar meters to Micropowermanager. It uses user_id & api_key for creating tokens for energy.',
                    'tail_tag' => 'MicroStar Meter',
                    'installation_command' => 'micro-star-meter:install',
                    'root_class' => 'MicroStarMeter',
                    'usage_type' => 'mini-grid'
                ],
                [
                    'name' => 'SunKingSHS',
                    'description' => 'This plugin integrates SunKing solar home systems to Micropowermanager. It uses client_id & client_secret for creating tokens for energy.',
                    'tail_tag' => 'SunKing SHS',
                    'installation_command' => 'sun-king-shs:install',
                    'root_class' => 'SunKingSHS',
                    'usage_type' => 'shs'
                ],
                [
                    'name' => 'GomeLongMeter',
                    'description' => 'This plugin integrates GomeLong meters to Micropowermanager. It uses. user_id & user_password for creating tokens for energy.',
                    'tail_tag' => 'GomeLong Meter',
                    'installation_command' => 'gome-long-meter:install',
                    'root_class' => 'GomeLongMeter',
                    'usage_type' => 'mini-grid'
                ],
                [
                    'name' => 'WavecomPayment',
                    'description' => 'This plugin developed for getting Wavecom(Senegal) payments into MicroPowerManager.',
                    'tail_tag' => null,
                    'installation_command' => 'wavecom-payment-provider:install',
                    'root_class' => 'WavecomPaymentProvider',
                    'usage_type' => 'general'
                ],
                [
                    'name' => 'DalyBms',
                    'description' => 'This plugin developed for managing e-bikes with daly bms.',
                    'tail_tag' => "Daly Bms",
                    'installation_command' => 'daly-bms:install',
                    'root_class' => 'DalyBms',
                    'usage_type' => 'e-bike'
                ],
                [
                    'name' => 'AngazaSHS',
                    'description' => 'This plugin integrates Angaza solar home systems to Micropowermanager. It uses client_id & client_secret for creating tokens for energy.',
                    'tail_tag' => 'Angaza SHS',
                    'installation_command' => 'angaza-shs:install',
                    'root_class' => 'AngazaSHS',
                    'usage_type' => 'shs'
                ]
            )
        );
    }
}
