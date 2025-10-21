<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;

class CurrencyController extends Controller {
    public function index(): ApiResource {
        $currency = file_get_contents(resource_path('data/currency.json'));
        $currency = json_decode($currency, true);

        return new ApiResource($currency);
    }
}
