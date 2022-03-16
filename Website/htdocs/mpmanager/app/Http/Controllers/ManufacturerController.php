<?php

namespace App\Http\Controllers;

use App\Models\Address\Address;
use App\Http\Requests\ManufacturerRequest;
use App\Http\Resources\ApiResource;
use App\Models\Manufacturer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ManufacturerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return ApiResource
     */
    public function index(): ApiResource
    {
        return new ApiResource(
            Manufacturer::paginate(15)
        );
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  ManufacturerRequest $request
     * @return ApiResource
     */
    public function store(ManufacturerRequest $request)
    {
        $manufacturer = new Manufacturer();
        $manufacturer->name = request('name');
        $manufacturer->contact_person = request('contact_person');
        $manufacturer->website = request('website');
        $manufacturer->api_name = request('api_name');

        $address = new Address();
        $address->city_id = request('city_id');
        $address->email = request('email');
        $address->phone = request('phone');
        $address->street = request('street');

        $manufacturer->save();
        $manufacturer->address()->save($address);

        return
            new ApiResource(
                $manufacturer
            );
    }

    /**
     * Display the specified resource.
     *
     * @param  Manufacturer $manufacturer
     * @return ApiResource
     */
    public function show(Manufacturer $manufacturer)
    {
        return new ApiResource(
            $manufacturer::with('address.city.country')->get()
        );
    }
}
