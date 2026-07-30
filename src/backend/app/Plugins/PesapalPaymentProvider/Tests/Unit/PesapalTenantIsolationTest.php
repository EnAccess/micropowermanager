<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Tests\Unit;

use App\Plugins\PesapalPaymentProvider\Http\Controllers\PesapalPublicController;
use App\Plugins\PesapalPaymentProvider\Services\PesapalCompanyHashService;
use App\Plugins\PesapalPaymentProvider\Services\PesapalCredentialService;
use App\Plugins\PesapalPaymentProvider\Services\PesapalIpnService;
use App\Plugins\PesapalPaymentProvider\Services\PesapalTransactionService;
use App\Services\CompanyService;
use Illuminate\Http\Request;
use Tests\TestCase;

class PesapalTenantIsolationTest extends TestCase {
    public function testPublicHashCannotAccessAnotherCompanyTenant(): void {
        config()->set('pesapal-payment-provider.company_hash_salt', 'pesapal-test-salt');

        $hashService = new PesapalCompanyHashService();
        $companyOneHash = $hashService->generatePermanentHash(1);
        $companyTwoToken = $hashService->generateHashFromCompanyId(2);

        $controller = new PesapalPublicController(
            $hashService,
            $this->createMock(PesapalTransactionService::class),
            $this->createMock(PesapalCredentialService::class),
            $this->createMock(PesapalIpnService::class),
            $this->createMock(CompanyService::class),
        );
        $request = Request::create(
            '/api/pesapal/public/payment/'.$companyOneHash.'?ct='.$companyTwoToken,
            'GET',
        );

        $response = $controller->showPaymentForm($request, $companyOneHash);

        $this->assertSame(400, $response->status());
        $this->assertSame(
            ['error' => 'Invalid company identifier'],
            $response->getData(true)
        );
    }
}
