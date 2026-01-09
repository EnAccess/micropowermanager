<?php

use App\Http\Controllers\AgentPerformanceMetricsController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\ApplianceController;
use App\Http\Controllers\ApplianceExportController;
use App\Http\Controllers\AppliancePaymentController;
use App\Http\Controllers\AppliancePersonController;
use App\Http\Controllers\ApplianceRateController;
use App\Http\Controllers\ApplianceTypeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClusterController;
use App\Http\Controllers\ClusterExportController;
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
use App\Http\Controllers\DeviceExportController;
use App\Http\Controllers\EBikeController;
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
use App\Http\Controllers\RegistrationTailController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RevenueController;
use App\Http\Controllers\RoleController;
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
require __DIR__.'/resources/Cities.php';
// Routes for Country resource
require __DIR__.'/resources/Countries.php';
// Routes for meter resource
require __DIR__.'/resources/Meters.php';
// Routes for Addresses resource
require __DIR__.'/resources/Addresses.php';
// Transaction routes
require __DIR__.'/api_paths/transactions.php';
// Agent routes
require __DIR__.'/resources/AgentApp.php';
// Agent Web panel routes
require __DIR__.'/resources/AgentWeb.php';
// Routes for CustomerRegistrationApp resource
require __DIR__.'/resources/CustomerRegistrationApp.php';
// Routes for Ticket Web panel routes
require __DIR__.'/resources/TicketWeb.php';

// JWT authentication
Route::group(['middleware' => 'api', 'prefix' => 'auth'], static function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);
});
// user
Route::group(['prefix' => 'users', 'middleware' => 'jwt.verify'], static function () {
    Route::post('/', [UserController::class, 'store'])->middleware('permission:users');
    Route::put('/{user}', [UserController::class, 'update'])->middleware('can:update,user');
    Route::get('/{user}', [UserController::class, 'show'])->middleware('can:view,user');
    Route::get('/', [UserController::class, 'index'])->middleware('permission:users');

    Route::group(['prefix' => '/{user}/addresses'], static function () {
        Route::post('/', [UserAddressController::class, 'store'])->middleware('permission:users');
        Route::put('/', [UserAddressController::class, 'update'])->middleware('can:update,user');
        Route::get('/', [UserAddressController::class, 'show'])->middleware('can:view,user');
    });
    Route::group(['prefix' => '/password'], static function () {
        Route::put('/{user}', [UserPasswordController::class, 'update'])->middleware('permission:users');
    });
});
Route::post('users/password', [UserPasswordController::class, 'forgotPassword']);
Route::get('users/password/validate/{token}', [UserPasswordController::class, 'validateResetToken']);
Route::post('users/password/confirm', [UserPasswordController::class, 'confirmReset']);

// Appliances
Route::group(['prefix' => 'appliances', 'middleware' => 'jwt.verify'], function () {
    Route::get('/', [ApplianceController::class, 'index'])->middleware('permission:appliances');
    Route::post('/', [ApplianceController::class, 'store'])->middleware('permission:appliances');
    Route::put('/{appliance}', [ApplianceController::class, 'update'])->middleware('permission:appliances');
    Route::delete('/{appliance}', [ApplianceController::class, 'destroy'])->middleware('permission:appliances');
    Route::group(['prefix' => 'person'], function () {
        Route::post('/{appliance}/people/{person}', [AppliancePersonController::class, 'store'])->middleware('permission:appliances');
        Route::get('/people/{person}', [AppliancePersonController::class, 'index'])->middleware('permission:appliances');
        Route::get('/people/detail/{applianceId}', [AppliancePersonController::class, 'show'])->middleware('permission:appliances');
        Route::get('/{appliancePersonId}/rates', [AppliancePersonController::class, 'getRates'])->middleware('permission:appliances');
        Route::get('/{appliancePersonId}/logs', [AppliancePersonController::class, 'getLogs'])->middleware('permission:appliances');
    });
    Route::group(['prefix' => 'types'], function () {
        Route::get('/', [ApplianceTypeController::class, 'index'])->middleware('permission:appliances');
        Route::post('/', [ApplianceTypeController::class, 'store'])->middleware('permission:appliances');
        Route::put('/{appliance_type}', [ApplianceTypeController::class, 'update'])->middleware('permission:appliances');
        Route::delete('/{appliance_type}', [ApplianceTypeController::class, 'destroy'])->middleware('permission:appliances');
    });

    Route::group(['prefix' => 'rates'], static function () {
        Route::put('/{appliance_rate}', [ApplianceRateController::class, 'update'])->middleware('permission:appliances');
    });

    Route::group(['prefix' => 'payment'], static function () {
        Route::post('/{appliance_person}', [AppliancePaymentController::class, 'store'])->middleware('permission:payments');
    });
});
// Clusters
Route::group(['prefix' => '/clusters', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/', [ClusterController::class, 'index']);
    Route::get('/{clusterId}', [ClusterController::class, 'show'])->where('clusterId', '[0-9]+');
    Route::post('/', [ClusterController::class, 'store'])->middleware('permission:settings');
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
Route::group(['prefix' => 'connection-groups', 'middleware' => ['jwt.verify', 'permission:settings']], static function () {
    Route::get('/', [ConnectionGroupController::class, 'index']);
    Route::post('/', [ConnectionGroupController::class, 'store']);
    Route::put('/{connectionGroupId}', [ConnectionGroupController::class, 'update']);
    Route::get('/{connectionGroupId}', [ConnectionGroupController::class, 'show']);
});
// Connection-Types
Route::group(['prefix' => 'connection-types', 'middleware' => ['jwt.verify', 'permission:settings']], static function () {
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
    Route::post('/', [MiniGridController::class, 'store'])->middleware('permission:settings');
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
    Route::get('/{personId}/flow/{year?}', [PaymentHistoryController::class, 'byYear'])->where('personId', '[0-9]+')->middleware('permission:payments');
    Route::get('/{person}/period', [PaymentHistoryController::class, 'getPaymentPeriod'])->where('personId', '[0-9]+')->middleware('permission:payments');
    Route::get('/debt/{personId}', [PaymentHistoryController::class, 'debts'])->where('personId', '[0-9]+')->middleware('permission:payments');
    Route::post('/overview', [PaymentHistoryController::class, 'getPaymentRange'])->middleware('permission:payments');
    Route::get('/{personId}/payments/{period}/{limit?}/{order?}', [PaymentHistoryController::class, 'show'])->where(
        'personId',
        '[0-9]+'
    )->middleware('permission:payments');
});
// People
Route::group(['prefix' => 'people', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/{personId}/meters', [PersonMeterController::class, 'show'])->middleware('permission:customers');
    Route::get('/{personId}/meters/geo', [MeterGeographicalInformationController::class, 'show'])->middleware('permission:customers');

    Route::get('/', [PersonController::class, 'index'])->middleware('permission:customers');
    // https://github.com/EnAccess/micropowermanager-customer-registration-app/issues/5
    Route::get('/all', [PersonController::class, 'index'])->middleware('permission:customers');
    Route::post('/', [PersonController::class, 'store'])->middleware('permission:customers');
    Route::get('/search', [PersonController::class, 'search'])->middleware('permission:customers');
    Route::get('/{personId}', [PersonController::class, 'show'])->middleware('permission:customers');
    Route::get('/{personId}/transactions', [PersonController::class, 'transactions'])->middleware('permission:transactions');
    Route::put('/{personId}', [PersonController::class, 'update'])->middleware('permission:customers');
    Route::delete('/{personId}', [PersonController::class, 'destroy'])->middleware('permission:customers');

    Route::get('/{personId}/addresses', [PersonAddressesController::class, 'show'])->middleware('permission:customers');
    Route::post('/{personId}/addresses', [PersonAddressesController::class, 'store'])->middleware('permission:customers');
    Route::put('/{personId}/addresses', [PersonAddressesController::class, 'update'])->middleware('permission:customers');
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
    Route::get('/main', [MainSettingsController::class, 'index'])->middleware('jwt.verify');
    // update requires auth and permission
    Route::put('/main/{mainSettings}', [MainSettingsController::class, 'update'])
        ->middleware(['jwt.verify', 'permission:settings']);
    Route::get('/sms-gateways', [MainSettingsController::class, 'getAvailableSmsGateways'])
        ->middleware('permission:settings');
    Route::get('/currency-list', [CurrencyController::class, 'index']);
});

// Roles (read-only role management)
Route::group(['prefix' => 'roles', 'middleware' => ['jwt.verify']], static function () {
    Route::get('/', [RoleController::class, 'index'])->middleware('permission:roles');
    Route::get('/permissions', [RoleController::class, 'permissions'])->middleware('permission:roles');
    Route::get('/details', [RoleController::class, 'details'])->middleware('permission:roles');
    Route::get('/user/{userId}', [RoleController::class, 'userRoles'])->middleware('permission:roles');
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
    Route::get('/', [ReportController::class, 'index'])->middleware('permission:reports');
    Route::get('/download/{id}', [ReportController::class, 'download'])->middleware('permission:reports');
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
    Route::put('/{subConnectionTypeId}', [SubConnectionTypeController::class, 'update']);
});
// Targets
Route::group(['prefix' => 'targets', 'middleware' => ['jwt.verify', 'permission:settings']], static function () {
    Route::get('/', [TargetController::class, 'index']);
    Route::post('/', [TargetController::class, 'store']);
    Route::get('/{targetId}', [TargetController::class, 'show']);
    Route::post('/slots', [TargetController::class, 'getSlotsForDate']);
});
// Tariffs
Route::group(['middleware' => ['jwt.verify', 'permission:settings'], 'prefix' => 'tariffs'], static function () {
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

Route::middleware('auth:api')->get('/user', fn (Request $request) => $request->user());

Route::group(['prefix' => 'mpm-plugins'], static function () {
    Route::get('/', [MpmPluginController::class, 'index']);
});

Route::group(['prefix' => 'registration-tails'], static function () {
    Route::get('/', [RegistrationTailController::class, 'index']);
    Route::put('/{registrationTail}', [RegistrationTailController::class, 'update']);
});
Route::group(['prefix' => 'plugins'], static function () {
    Route::get('/', [PluginController::class, 'index'])->middleware('permission:plugins');
    Route::put('/{mpmPluginId}', [PluginController::class, 'update'])->middleware('permission:plugins');
});

// API Keys management (requires web client auth token and admin permission)
Route::group(['middleware' => ['auth:api', 'permission:settings.api-keys']], static function () {
    Route::get('/api-keys', [ApiKeyController::class, 'index']);
    Route::post('/api-keys', [ApiKeyController::class, 'store']);
    Route::delete('/api-keys/{id}', [ApiKeyController::class, 'destroy']);
});

Route::get('/clusterlist', [ClusterController::class, 'index']);

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
    Route::get('/transactions', [TransactionExportController::class, 'download'])->middleware('permission:exports');
    Route::get('/debts', [OutstandingDebtsExportController::class, 'download'])->middleware('permission:exports');
    Route::get('/customers', [PersonExportController::class, 'download'])->middleware('permission:exports');
    Route::get('/devices', [DeviceExportController::class, 'download'])->middleware('permission:exports');
    Route::get('/appliances', [ApplianceExportController::class, 'download'])->middleware('permission:exports');
    Route::get('/clusters', [ClusterExportController::class, 'download'])->middleware('permission:exports');
});
Route::group(['prefix' => 'usage-types'], static function () {
    Route::get('/', [UsageTypeController::class, 'index']);
});
