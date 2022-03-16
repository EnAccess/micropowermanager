<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateConnectionGroupRequest;
use App\Http\Resources\ApiResource;
use App\Models\ConnectionGroup;

class ConnectionGroupController
{
    /**
     * @var ConnectionGroup
     */
    private $connectionGroup;

    public function __construct(ConnectionGroup $connectionGroup)
    {
        $this->connectionGroup = $connectionGroup;
    }

    public function index(): ApiResource
    {
        return new ApiResource($this->connectionGroup->get());
    }

    public function store(CreateConnectionGroupRequest $request): ApiResource
    {
        $name = $request->input('name');
        $this->connectionGroup->name = $name;
        $this->connectionGroup->save();
        return new ApiResource($this->connectionGroup);
    }

    public function update(ConnectionGroup $connectionGroup): ApiResource
    {
        $connectionGroup->update(request()->only(['name']));
        $connectionGroup->fresh();
        return new ApiResource($connectionGroup);
    }
}
