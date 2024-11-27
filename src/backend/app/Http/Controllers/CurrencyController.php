<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use Illuminate\Support\Facades\Storage;

class CurrencyController extends Controller {
    public function index(): ApiResource {
        $currency = Storage::disk('local')->get('currency.json');
        $currency = json_decode($currency, true);

        return new ApiResource($currency);
    }
}
