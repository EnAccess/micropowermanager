<?php

namespace App\Plugins\MicroStarMeter\Http\Controllers;

use App\Plugins\MicroStarMeter\Http\Requests\ImportCertificateRequest;
use App\Plugins\MicroStarMeter\Http\Resources\MicroStarResource;
use App\Plugins\MicroStarMeter\Services\MicroStarCertificateService;
use App\Plugins\MicroStarMeter\Services\MicroStarCredentialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MicroStarCertificateController extends Controller {
    public function __construct(
        private MicroStarCertificateService $certificateService,
        private MicroStarCredentialService $credentialService,
    ) {}

    public function store(ImportCertificateRequest $request): MicroStarResource {
        $credentials = $this->credentialService->getCredentials();

        return MicroStarResource::make($this->certificateService->upload($request, $credentials));
    }

    public function show(Request $request): JsonResponse {
        $credentials = $this->credentialService->getCredentials();
        $certificateName = $this->certificateService->getUploadedCertificate($credentials);

        return response()->json(['certificate_name' => $certificateName]);
    }
}
