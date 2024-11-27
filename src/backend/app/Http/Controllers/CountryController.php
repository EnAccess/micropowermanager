<?php

namespace App\Http\Controllers;

use App\Http\Requests\CountryRequest;
use App\Http\Resources\ApiResource;
use App\Models\Country;
use Illuminate\Support\Facades\Config;

class CountryController extends Controller {
    public function index(): ApiResource {
        return new ApiResource(
            Country::query()->paginate(
                Config::get('services.pagination')
            )
        );
    }

    public function show(Country $country): ApiResource {
        return ApiResource::make($country);
    }

    public function store(CountryRequest $request): ApiResource {
        return ApiResource::make(Country::query()->create(request()->only(['country_name', 'country_code'])));
    }
}
