<?php

use App\Http\Controllers\CompanyController;
use App\Http\Requests\AndroidAppRequest;
use App\Http\Resources\ApiResource;
use App\Models\Address\Address;
use App\Models\GeographicalInformation;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Meter\MeterTariff;
use App\Models\Meter\MeterType;
use App\Models\Person\Person;
use App\Services\PersonService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

//Routes for City resource
require_once 'resources/Cities.php';
//Routes for Country resource
require_once 'resources/Countries.php';
//Routes for meter resource
require_once 'resources/Meters.php';
//Routes for Addresses resource
require_once 'resources/Addresses.php';
// Transaction routes
require_once 'api_paths/transactions.php';
// Agent routes
require_once 'resources/AgentApp.php';
// Agent Web panel routes
require_once 'resources/AgentWeb.php';


//JWT authentication
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

    Route::group(['prefix' => '/{user}/addresses'], static function () {
        Route::post('/', 'UserAddressController@store');
        Route::put('/', 'UserAddressController@update');
        Route::get('/', 'UserAddressController@show');
    });
    Route::group(['prefix' => '/password'], static function () {
        Route::put('/{user}', 'UserPasswordController@update');
    });
});
Route::post('users/password', 'UserPasswordController@forgotPassword');
// Assets
Route::group(['prefix' => 'assets', 'middleware' => 'jwt.verify'], function () {
    Route::group(['prefix' => 'types'], function () {
        Route::get('/', 'AssetTypeController@index');
        Route::post('/', 'AssetTypeController@store');
        Route::put('/{asset_type}', 'AssetTypeController@update');
        Route::delete('/{asset_type}', 'AssetTypeController@destroy');

        Route::post('/{asset_type}/people/{person}', 'AssetPersonController@store');
        Route::get('/people/{person}', 'AssetPersonController@index');
        Route::get('/people/detail/{applianceId}', 'AssetPersonController@show');
    });

    Route::group(['prefix' => 'rates'], static function () {
        Route::put('/{appliance_rate}', 'AssetRateController@update');
    });

    Route::group(['prefix' => 'payment'], static function () {
        Route::post('/{appliance_person}', 'AppliancePaymentController@store');
    });

});
// Batteries
Route::group(['prefix' => 'batteries'], static function () {
    Route::post('/', 'BatteryController@store');
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
// Energies
Route::group(['prefix' => 'energies', 'middleware' => 'jwt.verify'], static function () {
    Route::post('/', 'EnergyController@store');

});
// Generation-Assets
Route::group(['prefix' => 'generation-assets', 'middleware' => 'jwt.verify'], static function () {
    Route::post('/grid', 'MiniGridFrequencyController@store');
    Route::get('/{miniGridId}/readings', 'GenerationAssetsController@show');
});
// Maintenance
Route::group(['prefix' => '/maintenance', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/', 'MaintenanceUserController@index');
    Route::post('/user', 'MaintenanceUserController@store')
        ->middleware('restriction:maintenance-user');
});
// Manufacturers
Route::group(['prefix' => 'manufacturers', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/', 'ManufacturerController@index');
    Route::get('/{manufacturer}', 'ManufacturerController@show');
    Route::post('/', 'ManufacturerController@store');
    Route::put('/{id}', 'ManufacturerController@update');

});
// Mini-Grids
Route::group(['prefix' => 'mini-grids', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/', 'MiniGridController@index');
    Route::post('/', 'MiniGridController@store');
    Route::get('/{miniGridId}', 'MiniGridController@show');
    Route::put('/{miniGridId}', 'MiniGridController@update')->middleware('restriction:enable-data-stream');
//
    Route::post('/{miniGridId}/transactions', 'MiniGridRevenueController@show');
    Route::post('/{miniGridId}/energy', 'MiniGridRevenueController@show');
    Route::get('/{miniGridId}/batteries', 'MiniGridBatteryController@show');
    Route::get('/{miniGridId}/solar', 'MiniGridSolarController@show');

});
// these routes are for the forecast-tool in jetson nano.
Route::group(['prefix' => 'mini-grids'], static function () {
    Route::get('/{miniGridId}/battery-readings', 'JetsonMiniGridBatteryController@show');
    Route::get('/{miniGridId}/energy-readings', 'JetsonMiniGridEnergyController@show');
    Route::get('/{miniGridId}/solar-readings', 'JetsonMiniGridSolarController@show');
    Route::get('/{miniGridId}/pv-readings', 'JetsonMiniGridPVController@show');
    Route::get('/{miniGridId}/weather-readings', 'JetsonMiniGridSolarController@show');
});
// PaymentHistories
Route::group(['prefix' => 'paymenthistories', 'middleware' => 'jwt.verify'], function () {
    Route::get('/{personId}/flow/{year?}', 'PaymentHistoryController@byYear')->where('personId', '[0-9]+');
    Route::get('/{personId}/period', 'PaymentHistoryController@getPaymentPeriod')->where('personId', '[0-9]+');
    Route::get('/debt/{personId}', 'PaymentHistoryController@debts')->where('personId', '[0-9]+');
    Route::post('/overview', 'PaymentHistoryController@getPaymentRange');
    Route::get('/{personId}/payments/{period}/{limit?}/{order?}', 'PaymentHistoryController@show')->where('personId',
        '[0-9]+');
});
// People
Route::group(['prefix' => 'people', 'middleware' => 'jwt.verify'], static function () {

    Route::get('/{personId}/meters', 'PersonMeterController@show');
    Route::get('/{personId}/meters/geo', 'MeterGeographicalInformationController@show');

    Route::get('/', 'PersonController@index');
    Route::post('/', 'PersonController@store');
    Route::get('/all', 'PersonController@list');
    Route::get('/search', 'PersonController@search');
    Route::get('/{personId}', 'PersonController@show');
    Route::get('/{personId}/transactions', 'PersonController@transactions');
    Route::put('/{personId}', 'PersonController@update');
    Route::delete('/{personId}', 'PersonController@destroy');

    Route::get('/{personId}/addresses', 'PersonAddressesController@show');
    Route::post('/{personId}/addresses', 'PersonAddressesController@store');
    Route::put('/{personId}/addresses', 'PersonAddressesController@update');


});
// PV
Route::group(['prefix' => 'pv'], static function () {
    Route::post('/', 'PVController@store')->middleware('data.controller');
    Route::get('/{miniGridId}', ['middleware' => 'jwt.verify', 'uses' => 'PVController@show']);

});
// Map Settings
Route::group(['prefix' => 'map-settings'], static function () {
    Route::get('/', 'MapSettingsController@index');
    Route::get('/key/{key}', 'MapSettingsController@checkBingApiKey');
    Route::put('/{mapSettings}', ['uses' => 'MapSettingsController@update', 'middleware' => 'jwt.verify']);
});
// Ticket Settings
Route::group(['prefix' => 'ticket-settings'], static function () {
    Route::get('/', 'TicketSettingsController@index');
    Route::put('/{ticketSettings}', ['uses' => 'TicketSettingsController@update', 'middleware' => 'jwt.verify']);
});

//Settings
Route::group(['prefix' => 'settings'], static function () {
    Route::get('/main', 'MainSettingsController@index');
    Route::put('/main/{mainSettings}', ['uses' => 'MainSettingsController@update', 'middleware' => 'jwt.verify']);
    Route::get('/mail', 'MailSettingsController@index');
    Route::post('/mail', ['uses' => 'MailSettingsController@store', 'middleware' => 'jwt.verify']);
    Route::put('/mail/{mailSettings}', ['uses' => 'MailSettingsController@update', 'middleware' => 'jwt.verify']);
    Route::get('/currency-list', 'CurrencyController@index');
    Route::get('/country-list', 'CountryListController@index');
    Route::get('/languages-list', 'LanguageController@index');
});
//Sms
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
Route::group(['prefix' => 'reports'], function () {
    Route::get('/', ['uses' => 'ReportController@index', 'middleware' => 'jwt.verify']);
    Route::get('/{id}/download', 'ReportController@download');
});
// Revenue
Route::group(['prefix' => 'revenue', 'middleware' => 'jwt.verify'], static function () {
    Route::post('/analysis/', 'RevenueController@analysis');
    Route::post('/trends/{id}', 'RevenueController@trending');
    Route::post('/', 'RevenueController@revenueData');
    Route::get('/tickets/{id}', 'RevenueController@ticketData');
});
// Sms
Route::group(['prefix' => 'sms', 'middleware' => 'jwt.verify'], static function () {
    Route::get('/{number}', 'SmsController@show');
    Route::get('/phone/{number}', 'SmsController@byPhone');
    Route::get('/{uuid}/confirm', 'SmsController@update');
    Route::get('/', 'SmsController@index');
    Route::get('search/{search}', 'SmsController@search');
    Route::post('/storeandsend', 'SmsController@storeAndSend');
    Route::post('/', 'SmsController@store');
    Route::post('/bulk', 'SmsController@storeBulk');

});
// Solar
Route::group(['prefix' => 'solar'], static function () {
    Route::post('/', 'SolarController@store');
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
    Route::get('/{id}', 'TargetController@show');
    Route::post('/slots', 'TargetController@getSlotsForDate');
});
// Tariffs
Route::group(['middleware' => 'jwt.verify', 'prefix' => 'tariffs'], static function () {
    Route::get('/', 'TariffController@index');
    Route::post('/', 'TariffController@store');
    Route::put('/{tariff}', 'TariffController@update');
    Route::get('/{tariff}', 'TariffController@show');
    Route::delete('/{tariff}', 'TariffController@destroy');
    Route::get('/{tariff}/usage-count', 'TariffController@usages');
    Route::put('/{tariff}/change-meters-tariff/{changeId}', 'TariffController@changeMetersTariff');

});
// Transactions
Route::group(['prefix' => 'transactions', 'middleware' => ['transaction.auth', 'transaction.request']],
    static function () {
        Route::post('/airtel', 'TransactionController@store');

        Route::post('/vodacom', ['as' => 'vodacomTransaction', 'uses' => 'TransactionController@store']);
        Route::post('/agent',
            ['as' => 'agent-transaction', 'uses' => 'TransactionController@store', 'middleware' => 'agent.balance']);

    });

Route::group(['prefix' => 'time-of-usages', 'middleware' => 'jwt.verify'], static function () {
    Route::delete('/{timeOfUsage}', 'TimeOfUsageController@destroy');
});

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('androidApp', static function (AndroidAppRequest $r) {
    try {
        DB::beginTransaction();
        //check if the meter id or the phone already exists
        $meter = Meter::query()->where('serial_number', $r->get('serial_number'))->first();
        $person = null;

        if ($meter === null) {
            $meter = new Meter();
            $meterParameter = new MeterParameter();
            $geoLocation = new GeographicalInformation();
        } else {

            $meterParameter = MeterParameter::query()->where('meter_id', $meter->id)->first();
            $geoLocation = $meterParameter->geo()->first();
            if ($geoLocation === null) {
                $geoLocation = new GeographicalInformation();
            }

            $person = Person::query()->whereHas('meters', static function ($q) use ($meterParameter) {
                return $q->where('id', $meterParameter->id);
            })->first();

        }

        if ($person === null) {
            $personService = App::make(PersonService::class);
            $person = $personService->createFromRequest($r);
        }

        $meter->serial_number = $r->get('serial_number');
        $meter->manufacturer()->associate(Manufacturer::query()->findOrFail($r->get('manufacturer')));
        $meter->meterType()->associate(MeterType::query()->findOrFail($r->get('meter_type')));
        $meter->updated_at = date('Y-m-d h:i:s');
        $meter->save();


        $geoLocation->points = $r->get('geo_points');


        $meterParameter->meter()->associate($meter);


        $meterParameter->owner()->associate($person);
        $meterParameter->tariff()->associate(MeterTariff::query()->findOrFail($r->get('tariff_id')));
        $meterParameter->save();
        $meterParameter->geo()->save($geoLocation);


        $address = new Address();
        $address = $address->newQuery()->create([
            'city_id' => request()->input('city_id') ?? 1,
        ]);
        $address->owner()->associate($meterParameter);

        $address->geo()->associate($meterParameter->geo);
        $address->save();

        //initializes a new Access Rate Payment for the next Period
        event('accessRatePayment.initialize', $meterParameter);
        // changes in_use parameter of the meter
        event('meterparameter.saved', $meterParameter->meter_id);
        DB::commit();

        return (new ApiResource($person))->response()->setStatusCode(201);
    } catch (Exception $e) {
        DB::rollBack();
        Log::critical('Error while adding new Customer', ['message' => $e->getMessage()]);

        return (new Response($e->getMessage()))->setStatusCode(409);
    }
});

Route::get('/clusterlist', 'ClusterController@index');

Route::post('/restrictions', 'RestrictionController@store');

Route::group(['prefix' => 'company'], static function () {
    Route::post('/', [CompanyController::class, 'store']);
});
