<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Routes for City resource
require 'resources/Cities.php';
// Routes for Country resource
require 'resources/Countries.php';
// Routes for meter resource
require 'resources/Meters.php';
// Routes for Addresses resource
require 'resources/Addresses.php';
// Transaction routes
require 'api_paths/transactions.php';
// Agent routes
require 'resources/AgentApp.php';
// Agent Web panel routes
require 'resources/AgentWeb.php';
// Routes for CustomerRegistrationApp resource
require 'resources/CustomerRegistrationApp.php';

// JWT authentication
Route::group(['middleware' => 'api', 'prefix' => 'auth'], static function () {
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::get('me', 'AuthController@me');
});
// user
Route::group(['prefix' => 'users', 'middleware' => 'jwt.verify'], static function () {
    Route::post('/', 'UserController@store');
    Route::put('/{user}', 'UserController@update');
    Route::get('/{user}', 'UserController@show');
    Route::get('/', 'UserController@index');

    Route::group(['prefix' => '/password'], static function () {
        Route::put('/{user}', 'UserPasswordController@update');
    });
});
Route::post('users/password', 'UserPasswordController@forgotPassword');

// Assets
Route::group(['prefix' => 'assets', 'middleware' => 'jwt.verify'], function () {
    Route::get('/', 'AssetController@index');
    Route::post('/', 'AssetController@store');
    Route::put('/{asset}', 'AssetController@update');
    Route::delete('/{asset}', 'AssetController@destroy');
    Route::group(['prefix' => 'person'], function () {
        Route::post('/{asset}/people/{person}', 'AssetPersonController@store');
        Route::get('/people/{person}', 'AssetPersonController@index');
        Route::get('/people/detail/{applianceId}', 'AssetPersonController@show');
    });
    Route::group(['prefix' => 'types'], function () {
        Route::get('/', 'AssetTypeController@index');
        Route::post('/', 'AssetTypeController@store');
        Route::put('/{asset_type}', 'AssetTypeController@update');
        Route::delete('/{asset_type}', 'AssetTypeController@destroy');
    });

    Route::group(['prefix' => 'rates'], static function () {
        Route::put('/{appliance_rate}', 'AssetRateController@update');
    });

    Route::group(['prefix' => 'payment'], static function () {
        Route::post('/{appliance_person}', 'AppliancePaymentController@store');
    });
});
// Clusters
Route::group(['prefix' => '/clusters', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/', 'ClusterController@index');
    Route::get('/{clusterId}', 'ClusterController@show')->where('clusterId', '[0-9]+');
    Route::post('/', 'ClusterController@store');
    Route::get('/{clusterId}/geo', 'ClusterController@showGeo');
    Route::get('/revenue', 'ClusterRevenueController@index');
    Route::get('/{clusterId}/revenue', 'ClusterRevenueController@show');
    Route::get('/{clusterId}/revenue/analysis', 'ClusterRevenueAnalysisController@show');
    Route::get('/{clusterId}/cities-revenue', 'ClusterMiniGridRevenueController@show');
});
// Dashboard data from cache
Route::group(['prefix' => '/dashboard', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/clusters', 'ClustersDashboardCacheDataController@index');
    Route::put('/clusters', 'ClustersDashboardCacheDataController@update');
    Route::get('/clusters/{clusterId}', 'ClustersDashboardCacheDataController@show');
    Route::get('/mini-grids', 'MiniGridDashboardCacheController@index');
    Route::put('/mini-grids', 'MiniGridDashboardCacheController@update');
    Route::get('/mini-grids/{miniGridId}', 'MiniGridDashboardCacheController@show');
});
// Connection-Groups
Route::group(['prefix' => 'connection-groups', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/', 'ConnectionGroupController@index');
    Route::post('/', 'ConnectionGroupController@store');
    Route::put('/{connectionGroupId}', 'ConnectionGroupController@update');
    Route::get('/{connectionGroupId}', 'ConnectionGroupController@show');
});
// Connection-Types
Route::group(['prefix' => 'connection-types', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/', 'ConnectionTypeController@index');
    Route::post('/', 'ConnectionTypeController@store');
    Route::get('/{connectionTypeId?}', 'ConnectionTypeController@show');
    Route::put('/{connectionTypeId}', 'ConnectionTypeController@update');
});
// Maintenance
Route::group(['prefix' => '/maintenance', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/', 'MaintenanceUserController@index');
    Route::post('/user', 'MaintenanceUserController@store');
});
// Manufacturers
Route::group(['prefix' => 'manufacturers', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/', 'ManufacturerController@index');
    Route::get('/{manufacturerId}', 'ManufacturerController@show');
    Route::post('/', 'ManufacturerController@store');
});
// Mini-Grids
Route::group(['prefix' => 'mini-grids', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/', 'MiniGridController@index');
    Route::post('/', 'MiniGridController@store');
    Route::get('/{miniGridId}', 'MiniGridController@show');

    Route::post('/{miniGridId}/transactions', 'MiniGridRevenueController@show');
    Route::post('/{miniGridId}/energy', 'MiniGridRevenueController@show');

    Route::group(['prefix' => '{miniGridId}'], static function () {
        Route::group(['prefix' => 'devices'], static function () {
            Route::get('/', 'MiniGridDeviceController@index');
        });
    });
});
// PaymentHistories
Route::group(['prefix' => 'paymenthistories', 'middleware' => 'jwt.verify'], function () {
    Route::get('/{personId}/flow/{year?}', 'PaymentHistoryController@byYear')->where('personId', '[0-9]+');
    Route::get('/{person}/period', 'PaymentHistoryController@getPaymentPeriod')->where('personId', '[0-9]+');
    Route::get('/debt/{personId}', 'PaymentHistoryController@debts')->where('personId', '[0-9]+');
    Route::post('/overview', 'PaymentHistoryController@getPaymentRange');
    Route::get('/{personId}/payments/{period}/{limit?}/{order?}', 'PaymentHistoryController@show')->where(
        'personId',
        '[0-9]+'
    );
});
// People
Route::group(['prefix' => 'people', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/{personId}/meters', 'PersonMeterController@show');
    Route::get('/{personId}/meters/geo', 'MeterGeographicalInformationController@show');

    Route::get('/', 'PersonController@index');
    Route::post('/', 'PersonController@store');
    Route::get('/search', 'PersonController@search');
    Route::get('/{personId}', 'PersonController@show');
    Route::get('/{personId}/transactions', 'PersonController@transactions');
    Route::put('/{personId}', 'PersonController@update');
    Route::delete('/{personId}', 'PersonController@destroy');

    Route::get('/{personId}/addresses', 'PersonAddressesController@show');
    Route::post('/{personId}/addresses', 'PersonAddressesController@store');
    Route::put('/{personId}/addresses', 'PersonAddressesController@update');
});
// Map Settings
Route::group(['prefix' => 'map-settings'], static function () {
    Route::get('/', 'MapSettingsController@index');
    Route::get('/key/{key}', 'MapSettingsController@checkBingApiKey');
    Route::put('/{mapSettings}', ['uses' => 'MapSettingsController@update', 'middleware' => 'jwt.verify']);
});

// Settings
Route::group(['prefix' => 'settings'], static function () {
    Route::get('/main', 'MainSettingsController@index');
    Route::put('/main/{mainSettings}', ['uses' => 'MainSettingsController@update', 'middleware' => 'jwt.verify']);
    Route::get('/mail', 'MailSettingsController@index');
    Route::post('/mail', ['uses' => 'MailSettingsController@store', 'middleware' => 'jwt.verify']);
    Route::put('/mail/{mailSettings}', ['uses' => 'MailSettingsController@update', 'middleware' => 'jwt.verify']);
    Route::get('/currency-list', 'CurrencyController@index');
    Route::get('/country-list', 'CountryListController@index');
});
// Sms
Route::group(['prefix' => 'sms-body'], static function () {
    Route::get('/', 'SmsBodyController@index');
    Route::put('/', 'SmsBodyController@update');
});
Route::group(['prefix' => 'sms-resend-information-key'], static function () {
    Route::get('/', 'SmsResendInformationKeyController@index');
    Route::put('/{smsResendInformationKey}', 'SmsResendInformationKeyController@update');
});
Route::group(['prefix' => 'sms-appliance-remind-rate'], static function () {
    Route::get('/', 'SmsApplianceRemindRateController@index');
    Route::put('/{smsApplianceRemindRate}', 'SmsApplianceRemindRateController@update');
    Route::post('/', 'SmsApplianceRemindRateController@store');
});
Route::group(['prefix' => 'sms-android-setting'], static function () {
    Route::get('/', 'SmsAndroidSettingController@index');
    Route::put('/{smsAndroidSetting}', 'SmsAndroidSettingController@update');
    Route::post('/', 'SmsAndroidSettingController@store');
    Route::delete('/{smsAndroidSetting}', 'SmsAndroidSettingController@destroy');
});
Route::group(['prefix' => 'sms-variable-default-value'], static function () {
    Route::get('/', 'SmsVariableDefaultValueController@index');
});
// Reports
Route::group(['prefix' => 'reports', 'middleware' => 'jwt.verify'], function () {
    Route::get('/', ['uses' => 'ReportController@index']);
});
Route::group(['prefix' => 'report-downloading'], function () {
    Route::get('/{id}/download/{slug}', 'ReportController@download');
});
// Revenue
Route::group(['prefix' => 'revenue', 'middleware' => 'jwt.verify'], static function () {
    Route::post('/', 'RevenueController@revenueData');
    Route::post('/trends/{id}', 'RevenueController@trending');
    Route::get('/tickets/{id}', 'RevenueController@ticketData');
});
// Sms
Route::group(['prefix' => 'sms', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/{number}', 'SmsController@show');
    Route::get('/phone/{number}', 'SmsController@byPhone');

    Route::get('/', 'SmsController@index');
    Route::get('search/{search}', 'SmsController@search');
    Route::post('/storeandsend', 'SmsController@storeAndSend');
    Route::post('/', 'SmsController@store');
    Route::post('/bulk', 'SmsController@storeBulk');
});
Route::group(['prefix' => 'sms-android-callback'], static function () {
    Route::get('/{uuid}/delivered/{slug}', 'SmsController@updateForDelivered');
    Route::get('/{uuid}/failed/{slug}', 'SmsController@updateForReject');
    Route::get('/{uuid}/sent/{slug}', 'SmsController@updateForSent');
});

// Sub-Connection-Types
Route::group(['prefix' => 'sub-connection-types', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/{connectionTypeId?}', 'SubConnectionTypeController@index');
    Route::post('/', 'SubConnectionTypeController@store');
    Route::get('/{id}', 'SubConnectionTypeController@show');
    Route::put('/{subConnectionTypeId}', 'SubConnectionTypeController@update');
});
// Targets
Route::group(['prefix' => 'targets', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/', 'TargetController@index');
    Route::post('/', 'TargetController@store');
    Route::get('/{targetId}', 'TargetController@show');
    Route::post('/slots', 'TargetController@getSlotsForDate');
});
// Tariffs
Route::group(['middleware' => 'jwt.verify', 'prefix' => 'tariffs'], static function () {
    Route::get('/', 'MeterTariffController@index');
    Route::get('/{meterTariffId}', 'MeterTariffController@show');
    Route::post('/', 'MeterTariffController@store');
    Route::put('/{meterTariffId}', 'MeterTariffController@update');
    Route::delete('/{meterTariffId}', 'MeterTariffController@destroy');
    Route::get('/{meterTariffId}/usage-count', 'MeterTariffController@showUsageCount');
    Route::put('/{meterTariffId}/change-meters-tariff/{changeId}', 'MeterTariffController@updateTariff');
    Route::put('/{meterSerial}/change-meter-tariff/{tariffId}', 'MeterTariffController@updateForMeter');
});
// Transactions
Route::group(
    ['prefix' => 'transactions', 'middleware' => ['transaction.auth', 'transaction.request']],
    static function () {
        Route::post(
            '/agent',
            ['as' => 'agent-transaction', 'uses' => 'TransactionController@store', 'middleware' => 'agent.balance']
        );
    }
);

Route::group(['prefix' => 'time-of-usages', 'middleware' => 'jwt.verify'], static function () {
    Route::delete('/{timeOfUsageId}', 'TimeOfUsageController@destroy');
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'mpm-plugins'], static function () {
    Route::get('/', 'MpmPluginController@index');
});

Route::group(['prefix' => 'registration-tails'], static function () {
    Route::get('/', 'RegistrationTailController@index');
    Route::put('/{registrationTail}', 'RegistrationTailController@update');
});
Route::group(['prefix' => 'plugins'], static function () {
    Route::get('/', 'PluginController@index');
    Route::put('/{mpmPluginId}', 'PluginController@update');
});

Route::get('/clusterlist', 'ClusterController@index');

Route::get('/protected-pages', 'ProtectedPageController@index');

Route::group(['prefix' => 'companies'], static function () {
    Route::post('/', 'CompanyController@store');
    Route::get('/{email}', 'CompanyController@get');
});
Route::group(['prefix' => 'devices'], static function () {
    Route::put('/{device}', 'DeviceController@update');
    Route::get('/', 'DeviceController@index');
});
Route::group(['prefix' => 'device-addresses'], function () {
    Route::post('/', 'DeviceAddressController@update');
});
Route::group(['prefix' => 'solar-home-systems'], static function () {
    Route::get('/', 'SolarHomeSystemController@index');
    Route::post('/', 'SolarHomeSystemController@store');
    Route::get('/search', 'SolarHomeSystemController@search');
});
Route::group(['prefix' => 'e-bikes'], static function () {
    Route::get('/', 'EBikeController@index');
    Route::post('/', 'EBikeController@store');
    Route::get('/search', 'EBikeController@search');
    Route::get('/{serialNumber}', 'EBikeController@show');
    Route::post('/switch', 'EBikeController@switch');
});
Route::group(['prefix' => 'export'], static function () {
    Route::get('/transactions/{slug}', 'TransactionExportController@download');
    Route::get('/debts/{slug}', 'OutstandingDebtsExportController@download');
});
Route::group(['prefix' => 'usage-types'], static function () {
    Route::get('/', 'UsageTypeController@index');
});
