<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\AssetPerson;
use App\Models\AssetType;
use App\Models\Person\Person;
use App\Services\AppliancePersonService;
use Illuminate\Http\Request;
use Ramsey\Collection\Collection;

class AssetPersonController extends Controller
{
    /**
     * @var AssetPerson
     */
    private $assetPerson;

    private $assetPersonService;


    public function __construct(
        AssetPerson $assetPerson,
        AppliancePersonService $assetPersonService,
    ) {
        $this->assetPerson = $assetPerson;
        $this->assetPersonService = $assetPersonService;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  AssetType $assetType
     * @param  Person    $person
     * @param  Request   $request
     * @return ApiResource
     */
    public function store(
        AssetType $assetType,
        Person $person,
        Request $request
    ): ApiResource {

        $assetPerson = $this->assetPersonService->createFromRequest($request, $person, $assetType);
        return new ApiResource($assetPerson);
    }

    /**
     * Display the specified resource.
     *
     * @param  Person  $person
     * @param  Request $request
     * @return ApiResource
     */
    public function index(Person $person, Request $request): ApiResource
    {
        $assets = $this->assetPerson::with('assetType', 'rates.logs', 'logs.owner')
            ->where('person_id', $person->id)
            ->get();
        return new ApiResource($assets);
    }

    public function show($applianceId): ApiResource
    {
        $appliance = $this->assetPersonService->getApplianceDetails($applianceId);

        return new ApiResource($appliance);
    }
}
