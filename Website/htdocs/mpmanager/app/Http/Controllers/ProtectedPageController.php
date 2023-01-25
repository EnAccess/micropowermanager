<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\ProtectedPage;


class ProtectedPageController
{
    public function index(): ApiResource
    {
        return ApiResource::make(ProtectedPage::query()->get());
    }
}