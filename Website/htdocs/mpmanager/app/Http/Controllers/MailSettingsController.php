<?php

namespace App\Http\Controllers;

use App\Http\Requests\MailSettingsRequest;
use App\Http\Resources\ApiResource;
use App\Models\MailSettings;
use App\Services\MailSettingsService;

class MailSettingsController extends Controller
{
    /**
     * @MailSettings
     */

    private $mailSettings;
    private $mailSettingsService;

    public function __construct(
        MailSettings $mailSettings,
        MailSettingsService $mailSettingsService
    ) {
        $this->mailSettings = $mailSettings;
        $this->mailSettingsService = $mailSettingsService;
    }

    public function index(): ApiResource
    {
        return new ApiResource($this->mailSettingsService->list());
    }

    public function update(MailSettingsRequest $request, MailSettings $mailSettings): ApiResource
    {
        return new ApiResource($this->mailSettingsService->update($request, $mailSettings));
    }

    public function store(MailSettingsRequest $request): ApiResource
    {
        return new ApiResource($this->mailSettingsService->create($request));
    }
}
