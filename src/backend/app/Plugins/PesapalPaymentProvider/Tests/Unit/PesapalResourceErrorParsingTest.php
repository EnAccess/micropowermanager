<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Tests\Unit;

use App\Plugins\PesapalPaymentProvider\Modules\Api\Resources\GetTransactionStatusResource;
use App\Plugins\PesapalPaymentProvider\Modules\Api\Resources\RegisterIpnResource;
use PHPUnit\Framework\TestCase;

class PesapalResourceErrorParsingTest extends TestCase {
    public function testGetTransactionStatusTreatsAllNullErrorObjectAsSuccess(): void {
        $resource = new GetTransactionStatusResource('https://base', 'token', 'ot_abc');
        $resource->setBody(json_encode([
            'payment_method' => 'Visa',
            'amount' => 100.0,
            'confirmation_code' => 'CONF123',
            'status_code' => 1,
            'merchant_reference' => 'mr_abc',
            'currency' => 'KES',
            'error' => ['error_type' => null, 'code' => null, 'message' => null],
            'status' => '200',
        ]));

        $this->assertNull($resource->getError());
        $this->assertSame(1, $resource->getStatusCode());
    }

    public function testGetTransactionStatusReportsPopulatedErrorMessage(): void {
        $resource = new GetTransactionStatusResource('https://base', 'token', 'ot_abc');
        $resource->setBody(json_encode([
            'status_code' => null,
            'error' => ['error_type' => 'api_error', 'code' => 'invalid_id', 'message' => 'Order not found'],
            'status' => '500',
        ]));

        $this->assertSame('Order not found', $resource->getError());
    }

    public function testGetTransactionStatusFallsBackToJsonWhenMessageMissing(): void {
        $resource = new GetTransactionStatusResource('https://base', 'token', 'ot_abc');
        $resource->setBody(json_encode([
            'error' => ['error_type' => 'api_error', 'code' => 'X', 'message' => null],
        ]));

        $this->assertSame('{"error_type":"api_error","code":"X","message":null}', $resource->getError());
    }

    public function testRegisterIpnTreatsAllNullErrorObjectAsSuccess(): void {
        $resource = new RegisterIpnResource('https://base', 'token', 'https://ipn');
        $resource->setBody(json_encode([
            'url' => 'https://ipn',
            'ipn_id' => 'ipn-uuid',
            'error' => ['error_type' => null, 'code' => null, 'message' => null],
            'status' => '200',
        ]));

        $this->assertNull($resource->getError());
        $this->assertSame('ipn-uuid', $resource->getIpnId());
    }
}
