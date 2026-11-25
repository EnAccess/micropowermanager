<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Catalog of all plugins shipped with MicroPowerManager.
 * The value is the plugin slug, which is how tenants, devices and API clients refer to a plugin.
 * When adding a plugin, add a case here and an arm to each match below.
 */
enum MpmPlugin: string {
    case SparkMeter = 'spark_meter';
    case SteamaMeter = 'steama_meter';
    case CalinMeter = 'calin_meter';
    case CalinSmartMeter = 'calin_smart_meter';
    case KelinMeter = 'kelin_meter';
    case StronMeter = 'stron_meter';
    case SwiftaPaymentProvider = 'swifta_payment_provider';
    case MesombPaymentProvider = 'mesomb_payment_provider';
    case BulkRegistration = 'bulk_registration';
    case ViberMessaging = 'viber_messaging';
    case WaveMoneyPaymentProvider = 'wave_money_payment_provider';
    case MicroStarMeter = 'micro_star_meter';
    case SunKingShs = 'sun_king_shs';
    case GomeLongMeter = 'gome_long_meter';
    case WavecomPaymentProvider = 'wavecom_payment_provider';
    case DalyBms = 'daly_bms';
    case AngazaShs = 'angaza_shs';
    case AfricasTalking = 'africas_talking';
    case VodacomMzPaymentProvider = 'vodacom_mz_payment_provider';
    case ChintMeter = 'chint_meter';
    case DemoMeterManufacturer = 'demo_meter_manufacturer';
    case DemoShsManufacturer = 'demo_shs_manufacturer';
    case OdysseyDataExport = 'odyssey_data_export';
    case Prospect = 'prospect';
    case PaystackPaymentProvider = 'paystack_payment_provider';
    case TextbeeSmsGateway = 'textbee_sms_gateway';
    case EcreeeETender = 'ecreee_e_tender';
    case SparkShs = 'spark_shs';
    case SmsTransactionParser = 'sms_transaction_parser';
    case PesapalPaymentProvider = 'pesapal_payment_provider';
    case SafaricomKePaymentProvider = 'safaricom_ke_payment_provider';
    case FlutterwavePaymentProvider = 'flutterwave_payment_provider';
    case StsMeterVend = 'sts_meter_vend';

    public function displayName(): string {
        return match ($this) {
            self::SparkMeter => 'Spark Meter',
            self::SteamaMeter => 'Steama Meter',
            self::CalinMeter => 'Calin Meter (Legacy Token API)',
            self::CalinSmartMeter => 'Calin Smart Meter',
            self::KelinMeter => 'Kelin Meter',
            self::StronMeter => 'Stron Meter',
            self::SwiftaPaymentProvider => 'Swifta Payment',
            self::MesombPaymentProvider => 'MeSomb Payment',
            self::BulkRegistration => 'Bulk Registration',
            self::ViberMessaging => 'Viber Messaging',
            self::WaveMoneyPaymentProvider => 'Wave Money Payment',
            self::MicroStarMeter => 'MicroStar Meter',
            self::SunKingShs => 'SunKing SHS',
            self::GomeLongMeter => 'GomeLong Meter',
            self::WavecomPaymentProvider => 'Wavecom Payment',
            self::DalyBms => 'Daly BMS',
            self::AngazaShs => 'Angaza SHS',
            self::AfricasTalking => "Africa's Talking",
            self::VodacomMzPaymentProvider => 'Vodacom Mozambique M-Pesa',
            self::ChintMeter => 'Chint Meter',
            self::DemoMeterManufacturer => 'Demo Meter Manufacturer',
            self::DemoShsManufacturer => 'Demo SHS Manufacturer',
            self::OdysseyDataExport => 'Odyssey Data Export',
            self::Prospect => 'Prospect',
            self::PaystackPaymentProvider => 'Paystack',
            self::TextbeeSmsGateway => 'TextBee SMS Gateway',
            self::EcreeeETender => 'ECREEE E-Tender',
            self::SparkShs => 'Spark SHS',
            self::SmsTransactionParser => 'SMS Transaction Parser',
            self::PesapalPaymentProvider => 'Pesapal',
            self::SafaricomKePaymentProvider => 'Safaricom Kenya M-Pesa',
            self::FlutterwavePaymentProvider => 'Flutterwave',
            self::StsMeterVend => 'STS Meter Vend',
        };
    }

    public function description(): string {
        return match ($this) {
            self::SparkMeter => 'This plugin uses KoiosAPI for the main authentication. After it got authenticated it uses the ThunderCloud API for basic CRUD operations. You need to enter the ThunderCloud Token on the site',
            self::SteamaMeter => 'This plugin integrates Steamaco meters to Micropowermanager. It uses the same  credentials as ui.steama.co for authentication. After it got authenticated, the plugin synchronizes Site, Customer ..',
            self::CalinMeter => 'This plugin integrates Calin meters to Micropowermanager. It uses user_id & api_key for creating tokens for energy.',
            self::CalinSmartMeter => 'This plugin integrates Calin meters to Micropowermanager. It uses company_name, user_name, password and password_vend for creating tokens for energy.',
            self::KelinMeter => 'This plugin integrates Kelim meters to Micropowermanager. It uses username & password for creating tokens for energy.',
            self::StronMeter => 'This plugin integrates Stron meters to Micropowermanager. It uses the api login credentials for authentication.',
            self::SwiftaPaymentProvider => 'This plugin developed for getting Swifta payments into MicroPowerManager.',
            self::MesombPaymentProvider => 'This plugin developed for getting MeSomb payments into MicroPowerManager.',
            self::BulkRegistration => "This plugin provides bulk registration of the company's existing records. NOTE: Please do not use this plugin to register your Spark & Stemaco meter records. These records will be synchronized automatically once you configure your credential settings for these plugins.",
            self::ViberMessaging => 'This plugin developed for the communication with customers throughout Viber messages.',
            self::WaveMoneyPaymentProvider => 'This plugin developed for getting WaveMoney payments into MicroPowerManager.',
            self::MicroStarMeter => 'This plugin integrates MicroStar meters to Micropowermanager. It uses user_id & api_key for creating tokens for energy.',
            self::SunKingShs => 'This plugin integrates SunKing solar home systems to Micropowermanager. It uses client_id & client_secret for creating tokens for energy.',
            self::GomeLongMeter => 'This plugin integrates GomeLong meters to Micropowermanager. It uses. user_id & user_password for creating tokens for energy.',
            self::WavecomPaymentProvider => 'This plugin developed for getting Wavecom(Senegal) payments into MicroPowerManager.',
            self::DalyBms => 'This plugin developed for managing e-bikes with daly bms.',
            self::AngazaShs => 'This plugin integrates Angaza solar home systems to Micropowermanager. It uses client_id & client_secret for creating tokens for energy.',
            self::AfricasTalking => 'This plugin developed for the communication with customers throughout Africas Talking.',
            self::VodacomMzPaymentProvider => 'This plugin enables payments via M-Pesa (Vodacom Mozambique).',
            self::ChintMeter => 'This plugin integrates Chint meters to Micropowermanager. It uses username & password for creating tokens for energy.',
            self::DemoMeterManufacturer => 'Demo manufacturer plugin for meters that generates realistic tokens and transactions without requiring real manufacturer API integration. Perfect for testing and demonstration purposes.',
            self::DemoShsManufacturer => 'Demo manufacturer plugin for Solar Home Systems (SHS) that generates realistic tokens and transactions without requiring real manufacturer API integration. Perfect for testing and demonstration purposes.',
            self::OdysseyDataExport => 'This plugin developed for OdysseyDataExport functionality.',
            self::Prospect => 'This plugin developed for data integration with Prospect energy data platform for aggregation, validation and visualization.',
            self::PaystackPaymentProvider => 'This plugin developed to payment via paystack payment provider',
            self::TextbeeSmsGateway => 'This plugin developed to allow you handle your SMS needs using only your mobile device.',
            self::EcreeeETender => 'This plugin integrates Ecreee e-tender integration to Micropowermanager.',
            self::SparkShs => 'This plugin adds SparkShs functionality to MicroPowerManager.',
            self::SmsTransactionParser => 'Parse incoming SMS messages from mobile money providers to create payment transactions',
            self::PesapalPaymentProvider => 'Accept online payments from customers via PesaPal hosted checkout',
            self::SafaricomKePaymentProvider => 'Safaricom M-PESA integration for MicroPowerManager.',
            self::FlutterwavePaymentProvider => 'This plugin developed to payment via flutterwave payment provider',
            self::StsMeterVend => 'Vends IEC 62055-41 STS tokens for registered STS meters through a self-hosted nxt-sts service.',
        };
    }

    public function usageType(): string {
        return match ($this) {
            self::SparkMeter,
            self::SteamaMeter,
            self::CalinMeter,
            self::CalinSmartMeter,
            self::KelinMeter,
            self::StronMeter,
            self::MicroStarMeter,
            self::GomeLongMeter,
            self::ChintMeter => 'mini-grid',
            self::SunKingShs,
            self::AngazaShs => 'shs',
            self::DalyBms => 'e-bike',
            default => 'general',
        };
    }

    public function installationCommand(): string {
        return str_replace('_', '-', $this->value).':install';
    }

    public function isPaymentProvider(): bool {
        return match ($this) {
            self::SwiftaPaymentProvider,
            self::MesombPaymentProvider,
            self::WaveMoneyPaymentProvider,
            self::WavecomPaymentProvider,
            self::VodacomMzPaymentProvider,
            self::PaystackPaymentProvider,
            self::PesapalPaymentProvider,
            self::SafaricomKePaymentProvider,
            self::FlutterwavePaymentProvider => true,
            default => false,
        };
    }
}
