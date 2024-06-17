<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\MenuItemsService;

class SidebarController extends Controller
{
    public function __construct(private MenuItemsService $menuItemsService)
    {
    }

    public function index(): ApiResource
    {
        return ApiResource::make($this->menuItemsService->getAll());
    }
}
