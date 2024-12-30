<?php

namespace App\Http\Controllers;

use App\Http\Requests\CountryRequest;
use App\Http\Resources\ApiResource;
use App\Models\Country;

class CountryController extends Controller {
    public function index(): ApiResource {
        return new ApiResource(
            Country::all()
        );
    }

    public function show(Country $country): ApiResource {
        return ApiResource::make($country);
    }

    public function store(CountryRequest $request): ApiResource {
        return ApiResource::make(Country::query()->create($request->only(['country_name', 'country_code'])));
    }
}
