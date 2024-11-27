<?php

namespace Inensus\MicroStarMeter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\MicroStarMeter\Http\Requests\ImportCertificateRequest;
use Inensus\MicroStarMeter\Http\Resources\MicroStarResource;
use Inensus\MicroStarMeter\Services\MicroStarCertificateService;
use Inensus\MicroStarMeter\Services\MicroStarCredentialService;

class MicroStarCertificateController extends Controller {
    public function __construct(
        private MicroStarCertificateService $certificateService,
        private MicroStarCredentialService $credentialService,
    ) {}

    public function store(ImportCertificateRequest $request) {
        $credentials = $this->credentialService->getCredentials();

        return MicroStarResource::make($this->certificateService->upload($request, $credentials));
    }

    public function show(Request $request) {
        $credentials = $this->credentialService->getCredentials();
        $certificateName = $this->certificateService->getUploadedCertificate($credentials);

        return response()->json(['certificate_name' => $certificateName]);
    }
}
