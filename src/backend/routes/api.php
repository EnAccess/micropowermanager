<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
use App\Http\Controllers\CountryListController;
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
Route::middleware('api')
    ->prefix('auth')
    ->group(static function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });
// user
Route::middleware('jwt.verify')
    ->prefix('users')
    ->group(static function () {
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::get('/', [UserController::class, 'index']);

        Route::prefix('{user}/addresses')
            ->group(static function () {
                Route::post('/', [UserAddressController::class, 'store']);
                Route::put('/', [UserAddressController::class, 'update']);
                Route::get('/', [UserAddressController::class, 'show']);
            });

        Route::prefix('password')
            ->group(static function () {
                Route::put('/{user}', [UserPasswordController::class, 'update']);
            });
    });
Route::post('users/password', [UserPasswordController::class, 'forgotPassword']);

// Assets
Route::middleware('jwt.verify')
    ->prefix('assets')
    ->group(function () {
        Route::get('/', [AssetController::class, 'index']);
        Route::post('/', [AssetController::class, 'store']);
        Route::put('/{asset}', [AssetController::class, 'update']);
        Route::delete('/{asset}', [AssetController::class, 'destroy']);

        Route::prefix('person')
            ->group(function () {
                Route::post('/{asset}/people/{person}', [AssetPersonController::class, 'store']);
                Route::get('/people/{person}', [AssetPersonController::class, 'index']);
                Route::get('/people/detail/{applianceId}', [AssetPersonController::class, 'show']);
            });

        Route::prefix('types')
            ->group(function () {
                Route::get('/', [AssetTypeController::class, 'index']);
                Route::post('/', [AssetTypeController::class, 'store']);
                Route::put('/{asset_type}', [AssetTypeController::class, 'update']);
                Route::delete('/{asset_type}', [AssetTypeController::class, 'destroy']);
            });

        Route::prefix('rates')
            ->group(static function () {
                Route::put('/{appliance_rate}', [AssetRateController::class, 'update']);
            });

        Route::prefix('payment')
            ->group(static function () {
                Route::post('/{appliance_person}', [AppliancePaymentController::class, 'store']);
            });
    });
// Clusters
Route::middleware('jwt.verify')
    ->prefix('/clusters')
    ->group(function () {
        Route::get('/', [ClusterController::class, 'index']);
        Route::get('/{clusterId}', [ClusterController::class, 'show'])->whereNumber('clusterId');
        Route::post('/', [ClusterController::class, 'store']);
        Route::get('/{clusterId}/geo', [ClusterController::class, 'showGeo']);
        Route::get('/revenue', [ClusterRevenueController::class, 'index']);
        Route::get('/{clusterId}/revenue', [ClusterRevenueController::class, 'show']);
        Route::get('/{clusterId}/revenue/analysis', [ClusterRevenueAnalysisController::class, 'show']);
        Route::get('/{clusterId}/cities-revenue', [ClusterMiniGridRevenueController::class, 'show']);
    });
// Dashboard data from cache
Route::middleware('jwt.verify')
    ->prefix('/dashboard')
    ->group(function () {
        Route::get('/clusters', [ClustersDashboardCacheDataController::class, 'index']);
        Route::put('/clusters', [ClustersDashboardCacheDataController::class, 'update']);
        Route::get('/clusters/{clusterId}', [ClustersDashboardCacheDataController::class, 'show']);
        Route::get('/mini-grids', [MiniGridDashboardCacheController::class, 'index']);
        Route::put('/mini-grids', [MiniGridDashboardCacheController::class, 'update']);
        Route::get('/mini-grids/{miniGridId}', [MiniGridDashboardCacheController::class, 'show']);
        Route::get('/agents', [AgentPerformanceMetricsController::class, 'index']);
    });
// Connection-Groups
Route::middleware('jwt.verify')
    ->prefix('connection-groups')
    ->group(function () {
        Route::get('/', [ConnectionGroupController::class, 'index']);
        Route::post('/', [ConnectionGroupController::class, 'store']);
        Route::put('/{connectionGroupId}', [ConnectionGroupController::class, 'update']);
        Route::get('/{connectionGroupId}', [ConnectionGroupController::class, 'show']);
    });
// Connection-Types
Route::middleware('jwt.verify')
    ->prefix('connection-types')
    ->group(function () {
        Route::get('/', [ConnectionTypeController::class, 'index']);
        Route::post('/', [ConnectionTypeController::class, 'store']);
        Route::get('/{connectionTypeId?}', [ConnectionTypeController::class, 'show']);
        Route::put('/{connectionTypeId}', [ConnectionTypeController::class, 'update']);
    });
// Maintenance
Route::middleware('jwt.verify')
    ->prefix('/maintenance')
    ->group(function () {
        Route::get('/', [MaintenanceUserController::class, 'index']);
        Route::post('/user', [MaintenanceUserController::class, 'store']);
    });
// Manufacturers
Route::middleware('jwt.verify')
    ->prefix('manufacturers')
    ->group(function () {
        Route::get('/', [ManufacturerController::class, 'index']);
        Route::get('/{manufacturerId}', [ManufacturerController::class, 'show']);
        Route::post('/', [ManufacturerController::class, 'store']);
    });
// Mini-Grids
Route::middleware('jwt.verify')
    ->prefix('mini-grids')
    ->group(function () {
        Route::get('/', [MiniGridController::class, 'index']);
        Route::post('/', [MiniGridController::class, 'store']);
        Route::get('/{miniGridId}', [MiniGridController::class, 'show']);

        Route::post('/{miniGridId}/transactions', [MiniGridRevenueController::class, 'show']);
        Route::post('/{miniGridId}/energy', [MiniGridRevenueController::class, 'show']);

        Route::prefix('{miniGridId}')->group(function () {
            Route::prefix('devices')->group(function () {
                Route::get('/', [MiniGridDeviceController::class, 'index']);
            });
        });
    });
// PaymentHistories
Route::middleware('jwt.verify')
    ->prefix('paymenthistories')
    ->group(function () {
        Route::get('/{personId}/flow/{year?}', [PaymentHistoryController::class, 'byYear'])
            ->whereNumber('personId');

        Route::get('/{person}/period', [PaymentHistoryController::class, 'getPaymentPeriod'])
            ->whereNumber('personId');

        Route::get('/debt/{personId}', [PaymentHistoryController::class, 'debts'])
            ->whereNumber('personId');

        Route::post('/overview', [PaymentHistoryController::class, 'getPaymentRange']);

        Route::get('/{personId}/payments/{period}/{limit?}/{order?}', [PaymentHistoryController::class, 'show'])
            ->whereNumber('personId');
    });
// People
Route::middleware('jwt.verify')
    ->prefix('people')
    ->group(function () {
        Route::get('/{personId}/meters', [PersonMeterController::class, 'show']);
        Route::get('/{personId}/meters/geo', [MeterGeographicalInformationController::class, 'show']);

        Route::get('/', [PersonController::class, 'index']);
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
    Route::put('/main/{mainSettings}', ['uses' => 'MainSettingsController@update', 'middleware' => 'jwt.verify']);
    Route::get('/mail', 'MailSettingsController@index');
    Route::post('/mail', [MailSettingsController::class, 'store'])
        ->middleware('jwt.verify');
    Route::put('/mail/{mailSettings}', [MailSettingsController::class, 'update'])->middleware('jwt.verify');
    Route::get('/currency-list', [CurrencyController::class, 'index']);
    Route::get('/country-list', [CountryListController::class, 'index']);
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
Route::middleware('jwt.verify')->group(function () {
    Route::prefix('reports')->group(function () {
        Route::get('/', [ReportController::class, 'index']);
    });
});
Route::group(['prefix' => 'report-downloading'], function () {
    Route::get('/{id}/download/{slug}', [ReportController::class, 'download']);
});
// Revenue
Route::middleware('jwt.verify')
    ->prefix('revenue')
    ->group(function () {
        Route::post('/', [RevenueController::class, 'revenueData']);
        Route::post('/trends/{id}', [RevenueController::class, 'trending']);
        Route::get('/tickets/{id}', [RevenueController::class, 'ticketData']);
    });
// Sms
Route::middleware('jwt.verify')
    ->prefix('sms')
    ->group(static function () {
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
Route::middleware('jwt.verify')
    ->prefix('sub-connection-types')
    ->group(static function () {
        Route::get('/{connectionTypeId?}', [SubConnectionTypeController::class, 'index']);
        Route::post('/', [SubConnectionTypeController::class, 'store']);
        Route::get('/{id}', [SubConnectionTypeController::class, 'show']);
        Route::put('/{subConnectionTypeId}', [SubConnectionTypeController::class, 'update']);
    });
// Targets
Route::middleware('jwt.verify')
    ->prefix('targets')
    ->group(static function () {
        Route::get('/', [TargetController::class, 'index']);
        Route::post('/', [TargetController::class, 'store']);
        Route::get('/{targetId}', [TargetController::class, 'show']);
        Route::post('/slots', [TargetController::class, 'getSlotsForDate']);
    });
// Tariffs
Route::middleware('jwt.verify')
    ->prefix('tariffs')
    ->group(static function () {
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
        Route::post(
            '/agent',
            ['as' => 'agent-transaction', 'uses' => 'TransactionController@store', 'middleware' => 'agent.balance']
        );
    }
);
Route::middleware(['transaction.auth', 'transaction.request'])
    ->prefix('transactions')
    ->group(static function () {
        Route::post('/agent', [TransactionController::class, 'store'])
            ->name('agent-transaction')
            ->middleware('agent.balance');
    });

Route::middleware('jwt.verify')
    ->prefix('time-of-usages')
    ->group(static function () {
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
Route::group(['prefix' => 'solar-home-systems'], static function () {
    Route::get('/', [SolarHomeSystemController::class, 'index']);
    Route::post('/', [SolarHomeSystemController::class, 'store']);
    Route::get('/{id}', [SolarHomeSystemController::class, 'show']);
    Route::get('/search', [SolarHomeSystemController::class, 'search']);
});
Route::group(['prefix' => 'e-bikes'], static function () {
    Route::get('/', [EBikeController::class, 'index']);
    Route::post('/', [EBikeController::class, 'store']);
    Route::get('/search', [EBikeController::class, 'search']);
    Route::get('/{serialNumber}', [EBikeController::class, 'show']);
    Route::post('/switch', [EBikeController::class, 'switch']);
});
Route::group(['prefix' => 'export'], static function () {
    Route::get('/transactions/{slug}', [TransactionExportController::class, 'download']);
    Route::get('/debts/{slug}', [OutstandingDebtsExportController::class, 'download']);
});
Route::group(['prefix' => 'usage-types'], static function () {
    Route::get('/', [UsageTypeController::class, 'index']);
});
