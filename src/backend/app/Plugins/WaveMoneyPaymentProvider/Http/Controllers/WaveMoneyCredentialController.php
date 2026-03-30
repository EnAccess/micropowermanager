<?php

namespace App\Plugins\WaveMoneyPaymentProvider\Http\Controllers;

use App\Plugins\WaveMoneyPaymentProvider\Http\Requests\WaveMoneyCredentialRequest;
use App\Plugins\WaveMoneyPaymentProvider\Http\Resources\WaveMoneyResource;
use App\Plugins\WaveMoneyPaymentProvider\Services\WaveMoneyCredentialService;
use App\Services\CompanyService;
use App\Services\DatabaseProxyManagerService;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\URL;

class WaveMoneyCredentialController extends Controller {
    public function __construct(
        private WaveMoneyCredentialService $credentialService,
        private DatabaseProxyManagerService $databaseProxyManagerService,
        private CompanyService $companyService,
    ) {}

    public function show(): WaveMoneyResource {
        return WaveMoneyResource::make($this->credentialService->getCredentials());
    }

    public function update(WaveMoneyCredentialRequest $request): WaveMoneyResource {
        $databaseProxy = $this->databaseProxyManagerService->findByEmail(auth('api')->user()->email);
        $companyId = $databaseProxy->getCompanyId();
        $company = $this->companyService->getById($companyId);
        $merchantId = $request->input('merchant_id');
        $secretKey = $request->input('secret_key');
        $id = $request->input('id');
        $merchantName = explode('_1', $company->name)[0];
        $callbackUrl = URL::to('/')."/api/wave-money/wave-money-transaction/callback/$companyId";
        $paymentUrl = str_replace('api.', '', URL::to('/'))."/#/wave-money/payment/$merchantName/$companyId";
        $resultUrl = str_replace('api.', '', URL::to('/'))."/#/wave-money/result/$merchantName/$companyId";

        $credentials = $this->credentialService->updateCredentials([
            'id' => $id,
            'merchant_id' => $merchantId,
            'secret_key' => $secretKey,
            'callback_url' => $callbackUrl,
            'payment_url' => $paymentUrl,
            'result_url' => $resultUrl,
            'merchant_name' => $merchantName,
        ]);

        return WaveMoneyResource::make($credentials);
    }
}
