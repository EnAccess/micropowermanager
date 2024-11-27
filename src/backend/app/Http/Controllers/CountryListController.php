<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use Illuminate\Support\Facades\Storage;

class CountryListController extends Controller {
    public function index(): ApiResource {
        $country = Storage::disk('local')->get('countries.json');
        $country = json_decode($country, true);

        return ApiResource::make($country);
    }
}
