<?php

use App\Http\Controllers\AgentPerformanceMetricsController;
use App\Http\Controllers\AppliancePaymentController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetPersonController;
use App\Http\Controllers\AssetRateController;
use App\Http\Controllers\AssetTypeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClusterController;
use App\Http\Controllers\ClusterMiniGridRevenueController;
use App\Http\Controllers\ClusterRevenueAnalysisController;
use App\Http\Controllers\ClusterRevenueController;
use App\Http\Controllers\ClustersDashboardCacheDataController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ConnectionGroupController;
use App\Http\Controllers\ConnectionTypeController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\DeviceAddressController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\EBikeController;
use App\Http\Controllers\MailSettingsController;
use App\Http\Controllers\MainSettingsController;
use App\Http\Controllers\MaintenanceUserController;
use App\Http\Controllers\ManufacturerController;
use App\Http\Controllers\MapSettingsController;
use App\Http\Controllers\MeterGeographicalInformationController;
use App\Http\Controllers\MeterTariffController;
use App\Http\Controllers\MiniGridController;
use App\Http\Controllers\MiniGridDashboardCacheController;
use App\Http\Controllers\MiniGridDeviceController;
use App\Http\Controllers\MiniGridRevenueController;
use App\Http\Controllers\MpmPluginController;
use App\Http\Controllers\OutstandingDebtsExportController;
use App\Http\Controllers\PaymentHistoryController;
use App\Http\Controllers\PersonAddressesController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\PersonExportController;
use App\Http\Controllers\PersonMeterController;
use App\Http\Controllers\PluginController;
use App\Http\Controllers\ProtectedPageController;
use App\Http\Controllers\RegistrationTailController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RevenueController;
use App\Http\Controllers\SmsAndroidSettingController;
use App\Http\Controllers\SmsApplianceRemindRateController;
use App\Http\Controllers\SmsBodyController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\SmsResendInformationKeyController;
use App\Http\Controllers\SmsVariableDefaultValueController;
use App\Http\Controllers\SolarHomeSystemController;
use App\Http\Controllers\SubConnectionTypeController;
use App\Http\Controllers\TargetController;
use App\Http\Controllers\TimeOfUsageController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransactionExportController;
use App\Http\Controllers\UsageTypeController;
use App\Http\Controllers\UserAddressController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPasswordController;
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
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);
});
// user
Route::group(['prefix' => 'users', 'middleware' => 'jwt.verify'], static function () {
    Route::post('/', [UserController::class, 'store']);
    Route::put('/{user}', [UserController::class, 'update']);
    Route::get('/{user}', [UserController::class, 'show']);
    Route::get('/', [UserController::class, 'index']);

    Route::group(['prefix' => '/{user}/addresses'], static function () {
        Route::post('/', [UserAddressController::class, 'store']);
        Route::put('/', [UserAddressController::class, 'update']);
        Route::get('/', [UserAddressController::class, 'show']);
    });
    Route::group(['prefix' => '/password'], static function () {
        Route::put('/{user}', [UserPasswordController::class, 'update']);
    });
});
Route::post('users/password', [UserPasswordController::class, 'forgotPassword']);

// Assets
Route::group(['prefix' => 'assets', 'middleware' => 'jwt.verify'], function () {
    Route::get('/', [AssetController::class, 'index']);
    Route::post('/', [AssetController::class, 'store']);
    Route::put('/{asset}', [AssetController::class, 'update']);
    Route::delete('/{asset}', [AssetController::class, 'destroy']);
    Route::group(['prefix' => 'person'], function () {
        Route::post('/{asset}/people/{person}', [AssetPersonController::class, 'store']);
        Route::get('/people/{person}', [AssetPersonController::class, 'index']);
        Route::get('/people/detail/{applianceId}', [AssetPersonController::class, 'show']);
    });
    Route::group(['prefix' => 'types'], function () {
        Route::get('/', [AssetTypeController::class, 'index']);
        Route::post('/', [AssetTypeController::class, 'store']);
        Route::put('/{asset_type}', [AssetTypeController::class, 'update']);
        Route::delete('/{asset_type}', [AssetTypeController::class, 'destroy']);
    });

    Route::group(['prefix' => 'rates'], static function () {
        Route::put('/{appliance_rate}', [AssetRateController::class, 'update']);
    });

    Route::group(['prefix' => 'payment'], static function () {
        Route::post('/{appliance_person}', [AppliancePaymentController::class, 'store']);
    });
});
// Clusters
Route::group(['prefix' => '/clusters', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/', [ClusterController::class, 'index']);
    Route::get('/{clusterId}', [ClusterController::class, 'show'])->where('clusterId', '[0-9]+');
    Route::post('/', [ClusterController::class, 'store']);
    Route::get('/{clusterId}/geo', [ClusterController::class, 'showGeo']);
    Route::get('/revenue', [ClusterRevenueController::class, 'index']);
    Route::get('/{clusterId}/revenue', [ClusterRevenueController::class, 'show']);
    Route::get('/{clusterId}/revenue/analysis', [ClusterRevenueAnalysisController::class, 'show']);
    Route::get('/{clusterId}/cities-revenue', [ClusterMiniGridRevenueController::class, 'show']);
});
// Dashboard data from cache
Route::group(['prefix' => '/dashboard', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/clusters', [ClustersDashboardCacheDataController::class, 'index']);
    Route::put('/clusters', [ClustersDashboardCacheDataController::class, 'update']);
    Route::get('/clusters/{clusterId}', [ClustersDashboardCacheDataController::class, 'show']);
    Route::get('/mini-grids', [MiniGridDashboardCacheController::class, 'index']);
    Route::put('/mini-grids', [MiniGridDashboardCacheController::class, 'update']);
    Route::get('/mini-grids/{miniGridId}', [MiniGridDashboardCacheController::class, 'show']);
    Route::get('/agents', [AgentPerformanceMetricsController::class, 'index']);
});
// Connection-Groups
Route::group(['prefix' => 'connection-groups', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/', [ConnectionGroupController::class, 'index']);
    Route::post('/', [ConnectionGroupController::class, 'store']);
    Route::put('/{connectionGroupId}', [ConnectionGroupController::class, 'update']);
    Route::get('/{connectionGroupId}', [ConnectionGroupController::class, 'show']);
});
// Connection-Types
Route::group(['prefix' => 'connection-types', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/', [ConnectionTypeController::class, 'index']);
    Route::post('/', [ConnectionTypeController::class, 'store']);
    Route::get('/{connectionTypeId?}', [ConnectionTypeController::class, 'show']);
    Route::put('/{connectionTypeId}', [ConnectionTypeController::class, 'update']);
});
// Maintenance
Route::group(['prefix' => '/maintenance', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/', [MaintenanceUserController::class, 'index']);
    Route::post('/user', [MaintenanceUserController::class, 'store']);
});
// Manufacturers
Route::group(['prefix' => 'manufacturers', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/', [ManufacturerController::class, 'index']);
    Route::get('/{manufacturerId}', [ManufacturerController::class, 'show']);
    Route::post('/', [ManufacturerController::class, 'store']);
});
// Mini-Grids
Route::group(['prefix' => 'mini-grids', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/', [MiniGridController::class, 'index']);
    Route::post('/', [MiniGridController::class, 'store']);
    Route::get('/{miniGridId}', [MiniGridController::class, 'show']);

    Route::post('/{miniGridId}/transactions', [MiniGridRevenueController::class, 'show']);
    Route::post('/{miniGridId}/energy', [MiniGridRevenueController::class, 'show']);

    Route::group(['prefix' => '{miniGridId}'], static function () {
        Route::group(['prefix' => 'devices'], static function () {
            Route::get('/', [MiniGridDeviceController::class, 'index']);
        });
    });
});
// PaymentHistories
Route::group(['prefix' => 'paymenthistories', 'middleware' => 'jwt.verify'], function () {
    Route::get('/{personId}/flow/{year?}', [PaymentHistoryController::class, 'byYear'])->where('personId', '[0-9]+');
    Route::get('/{person}/period', [PaymentHistoryController::class, 'getPaymentPeriod'])->where('personId', '[0-9]+');
    Route::get('/debt/{personId}', [PaymentHistoryController::class, 'debts'])->where('personId', '[0-9]+');
    Route::post('/overview', [PaymentHistoryController::class, 'getPaymentRange']);
    Route::get('/{personId}/payments/{period}/{limit?}/{order?}', [PaymentHistoryController::class, 'show'])->where(
        'personId',
        '[0-9]+'
    );
});
// People
Route::group(['prefix' => 'people', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/{personId}/meters', [PersonMeterController::class, 'show']);
    Route::get('/{personId}/meters/geo', [MeterGeographicalInformationController::class, 'show']);

    Route::get('/', [PersonController::class, 'index']);
    // https://github.com/EnAccess/micropowermanager-customer-registration-app/issues/5
    Route::get('/all', [PersonController::class, 'index']);
    Route::post('/', [PersonController::class, 'store']);
    Route::get('/search', [PersonController::class, 'search']);
    Route::get('/{personId}', [PersonController::class, 'show']);
    Route::get('/{personId}/transactions', [PersonController::class, 'transactions']);
    Route::put('/{personId}', [PersonController::class, 'update']);
    Route::delete('/{personId}', [PersonController::class, 'destroy']);

    Route::get('/{personId}/addresses', [PersonAddressesController::class, 'show']);
    Route::post('/{personId}/addresses', [PersonAddressesController::class, 'store']);
    Route::put('/{personId}/addresses', [PersonAddressesController::class, 'update']);
});
// Map Settings
Route::group(['prefix' => 'map-settings'], static function () {
    Route::get('/', [MapSettingsController::class, 'index']);
    Route::get('/key/{key}', [MapSettingsController::class, 'checkBingApiKey']);
    Route::put('/{mapSettings}', [MapSettingsController::class, 'update'])
        ->middleware('jwt.verify');
});

// Settings
Route::group(['prefix' => 'settings'], static function () {
    Route::get('/main', [MainSettingsController::class, 'index']);
    Route::put('/main/{mainSettings}', [MainSettingsController::class, 'update'])
        ->middleware('jwt.verify');
    Route::get('/mail', [MailSettingsController::class, 'index']);
    Route::post('/mail', [MailSettingsController::class, 'store'])
        ->middleware('jwt.verify');
    Route::put('/mail/{mailSettings}', [MailSettingsController::class, 'update'])
        ->middleware('jwt.verify');
    Route::get('/currency-list', [CurrencyController::class, 'index']);
});
// Sms
Route::group(['prefix' => 'sms-body'], static function () {
    Route::get('/', [SmsBodyController::class, 'index']);
    Route::put('/', [SmsBodyController::class, 'update']);
});
Route::group(['prefix' => 'sms-resend-information-key'], static function () {
    Route::get('/', [SmsResendInformationKeyController::class, 'index']);
    Route::put('/{smsResendInformationKey}', [SmsResendInformationKeyController::class, 'update']);
});
Route::group(['prefix' => 'sms-appliance-remind-rate'], static function () {
    Route::get('/', [SmsApplianceRemindRateController::class, 'index']);
    Route::put('/{smsApplianceRemindRate}', [SmsApplianceRemindRateController::class, 'update']);
    Route::post('/', [SmsApplianceRemindRateController::class, 'store']);
});
Route::group(['prefix' => 'sms-android-setting'], static function () {
    Route::get('/', [SmsAndroidSettingController::class, 'index']);
    Route::put('/{smsAndroidSetting}', [SmsAndroidSettingController::class, 'update']);
    Route::post('/', [SmsAndroidSettingController::class, 'store']);
    Route::delete('/{smsAndroidSetting}', [SmsAndroidSettingController::class, 'destroy']);
});
Route::group(['prefix' => 'sms-variable-default-value'], static function () {
    Route::get('/', [SmsVariableDefaultValueController::class, 'index']);
});
// Reports
Route::group(['prefix' => 'reports', 'middleware' => 'jwt.verify'], function () {
    Route::get('/', [ReportController::class, 'index']);
});
Route::group(['prefix' => 'report-downloading'], function () {
    Route::get('/{id}/download/{slug}', [ReportController::class, 'download']);
});
// Revenue
Route::group(['prefix' => 'revenue', 'middleware' => 'jwt.verify'], static function () {
    Route::post('/', [RevenueController::class, 'revenueData']);
    Route::post('/trends/{id}', [RevenueController::class, 'trending']);
    Route::get('/tickets/{id}', [RevenueController::class, 'ticketData']);
});
// Sms
Route::group(['prefix' => 'sms', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/{number}', [SmsController::class, 'show']);
    Route::get('/phone/{number}', [SmsController::class, 'byPhone']);

    Route::get('/', [SmsController::class, 'index']);
    Route::get('search/{search}', [SmsController::class, 'search']);
    Route::post('/storeandsend', [SmsController::class, 'storeAndSend']);
    Route::post('/', [SmsController::class, 'store']);
    Route::post('/bulk', [SmsController::class, 'storeBulk']);
});
Route::group(['prefix' => 'sms-android-callback'], static function () {
    Route::get('/{uuid}/delivered/{slug}', [SmsController::class, 'updateForDelivered']);
    Route::get('/{uuid}/failed/{slug}', [SmsController::class, 'updateForReject']);
    Route::get('/{uuid}/sent/{slug}', [SmsController::class, 'updateForSent']);
});

// Sub-Connection-Types
Route::group(['prefix' => 'sub-connection-types', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/{connectionTypeId?}', [SubConnectionTypeController::class, 'index']);
    Route::post('/', [SubConnectionTypeController::class, 'store']);
    Route::get('/{id}', [SubConnectionTypeController::class, 'show']);
    Route::put('/{subConnectionTypeId}', [SubConnectionTypeController::class, 'update']);
});
// Targets
Route::group(['prefix' => 'targets', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/', [TargetController::class, 'index']);
    Route::post('/', [TargetController::class, 'store']);
    Route::get('/{targetId}', [TargetController::class, 'show']);
    Route::post('/slots', [TargetController::class, 'getSlotsForDate']);
});
// Tariffs
Route::group(['middleware' => 'jwt.verify', 'prefix' => 'tariffs'], static function () {
    Route::get('/', [MeterTariffController::class, 'index']);
    Route::get('/{meterTariffId}', [MeterTariffController::class, 'show']);
    Route::post('/', [MeterTariffController::class, 'store']);
    Route::put('/{meterTariffId}', [MeterTariffController::class, 'update']);
    Route::delete('/{meterTariffId}', [MeterTariffController::class, 'destroy']);
    Route::get('/{meterTariffId}/usage-count', [MeterTariffController::class, 'showUsageCount']);
    Route::put('/{meterTariffId}/change-meters-tariff/{changeId}', [MeterTariffController::class, 'updateTariff']);
    Route::put('/{meterSerial}/change-meter-tariff/{tariffId}', [MeterTariffController::class, 'updateForMeter']);
});
// Transactions
Route::group(
    ['prefix' => 'transactions', 'middleware' => ['transaction.auth', 'transaction.request']],
    static function () {
        Route::post('/agent', [TransactionController::class, 'store'])
            ->name('agent-transaction')
            ->middleware('agent.balance');
    }
);

Route::group(['prefix' => 'time-of-usages', 'middleware' => 'jwt.verify'], static function () {
    Route::delete('/{timeOfUsageId}', [TimeOfUsageController::class, 'destroy']);
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'mpm-plugins'], static function () {
    Route::get('/', [MpmPluginController::class, 'index']);
});

Route::group(['prefix' => 'registration-tails'], static function () {
    Route::get('/', [RegistrationTailController::class, 'index']);
    Route::put('/{registrationTail}', [RegistrationTailController::class, 'update']);
});
Route::group(['prefix' => 'plugins'], static function () {
    Route::get('/', [PluginController::class, 'index']);
    Route::put('/{mpmPluginId}', [PluginController::class, 'update']);
});

Route::get('/clusterlist', [ClusterController::class, 'index']);

Route::group(['prefix' => 'protected-pages'], static function () {
    Route::get('/', [ProtectedPageController::class, 'index']);
    Route::post('/compare', [ProtectedPageController::class, 'compareProtectedPagePassword']);
});

Route::group(['prefix' => 'companies'], static function () {
    Route::post('/', [CompanyController::class, 'store']);
    Route::get('/{email}', [CompanyController::class, 'get']);
});
Route::group(['prefix' => 'devices'], static function () {
    Route::put('/{device}', [DeviceController::class, 'update']);
    Route::get('/', [DeviceController::class, 'index']);
});
Route::group(['prefix' => 'device-addresses'], function () {
    Route::post('/', [DeviceAddressController::class, 'update']);
});
Route::group(['prefix' => 'solar-home-systems', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/', [SolarHomeSystemController::class, 'index']);
    Route::post('/', [SolarHomeSystemController::class, 'store']);
    Route::get('/search', [SolarHomeSystemController::class, 'search']);
    Route::get('/{id}', [SolarHomeSystemController::class, 'show']);
    Route::get('/{id}/transactions', [SolarHomeSystemController::class, 'transactions']);
});
Route::group(['prefix' => 'e-bikes'], static function () {
    Route::get('/', [EBikeController::class, 'index']);
    Route::post('/', [EBikeController::class, 'store']);
    Route::get('/search', [EBikeController::class, 'search']);
    Route::get('/{serialNumber}', [EBikeController::class, 'show']);
    Route::post('/switch', [EBikeController::class, 'switch']);
});
Route::group(['prefix' => 'export', 'middleware' => 'api'], static function () {
    Route::get('/transactions', [TransactionExportController::class, 'download']);
    Route::get('/debts', [OutstandingDebtsExportController::class, 'download']);
    Route::get('/customers', [PersonExportController::class, 'download']);
});
Route::group(['prefix' => 'usage-types'], static function () {
    Route::get('/', [UsageTypeController::class, 'index']);
});
