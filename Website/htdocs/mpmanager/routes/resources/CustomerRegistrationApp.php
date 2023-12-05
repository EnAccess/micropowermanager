<?php

use App\Http\Requests\AndroidAppRequest;
use App\Http\Resources\ApiResource;
use App\Services\AddressesService;
use App\Services\AddressGeographicalInformationService;
use App\Services\GeographicalInformationService;
use App\Services\MeterService;
use App\Services\PersonService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use MPM\Device\DeviceAddressService;
use MPM\Device\DeviceService;
use MPM\Meter\MeterDeviceService;


Route::group(['prefix' => 'customer-registration-app'], static function () {
    Route::get('/people', 'PersonController@index');
    Route::get('/manufacturers', 'ManufacturerController@index');
    Route::get('/meter-types', 'MeterTypeController@index');
    Route::get('/tariffs', 'MeterTariffController@index');
    Route::get('/cities', 'CityController@index');
    Route::get('/connection-groups', 'ConnectionGroupController@index');
    Route::get('/connection-types', 'ConnectionTypeController@index');
    Route::get('/sub-connection-types', 'SubConnectionTypeController@index');
    Route::post('/', 'CustomerRegistrationAppController@store');
});