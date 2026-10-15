<?php

namespace App\Plugins\OpenPaygo\Tests\Unit;

use App\Models\Device;
use App\Models\Manufacturer;
use App\Models\SolarHomeSystem;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use App\Plugins\OpenPaygo\Models\OpenPaygoIssuanceReservation;
use Illuminate\Foundation\Testing\TestCase;

class OpenPaygoPaymentStatusTest extends TestCase {
    public function testCompletedReservationRequiresPersistedToken(): void {
        $transaction = $this->transaction(OpenPaygoIssuanceReservation::STATE_COMPLETED);
        $this->assertSame('pending', $transaction->token_issuance['status']);
        $token = new Token();
        $transaction->setRelation('token', $token);
        $this->assertSame('pending', $transaction->token_issuance['status']);
        $token->exists = true;
        $token->id = 1;
        $this->assertSame('completed', $transaction->token_issuance['status']);
    }

    public function testGeneratedResultPersistenceFailureRequiresRecovery(): void {
        $transaction = $this->transaction(OpenPaygoIssuanceReservation::STATE_GENERATED);
        $transaction->openPaygoIssuance->last_error = 'Stored generator result requires recovery.';
        $this->assertSame([
            'status' => 'blocked', 'message' => 'Stored generator result requires recovery.',
        ], $transaction->token_issuance);
    }

    public function testBlockedReservationSurfacesSafeReason(): void {
        $transaction = $this->transaction(OpenPaygoIssuanceReservation::STATE_BLOCKED);
        $transaction->openPaygoIssuance->last_error = 'Device time divider is missing.';
        $this->assertSame([
            'status' => 'blocked', 'message' => 'Device time divider is missing.',
        ], $transaction->token_issuance);
    }

    public function testUncertainOutcomeRequiresReconciliation(): void {
        $status = $this->transaction(OpenPaygoIssuanceReservation::STATE_UNCERTAIN)->token_issuance;
        $this->assertSame('uncertain', $status['status']);
        $this->assertStringContainsString('reconcile', $status['message']);
    }

    public function testOtherManufacturersHaveNoIssuanceStatus(): void {
        $transaction = new Transaction();
        $transaction->setRelation('device', null)->setRelation('openPaygoIssuance', null);
        $this->assertNull($transaction->token_issuance);
    }

    public function testMissingConfigurationIsBlocked(): void {
        $manufacturer = new Manufacturer(['api_name' => 'OpenPaygoApi']);
        $solarHomeSystem = new SolarHomeSystem();
        $solarHomeSystem->setRelation('manufacturer', $manufacturer);
        $device = new Device();
        $device->setRelation('device', $solarHomeSystem)->setRelation('openPaygoConfiguration', null);
        $transaction = new Transaction();
        $transaction->setRelation('device', $device)->setRelation('openPaygoIssuance', null);
        $this->assertSame('blocked', $transaction->token_issuance['status']);
    }

    private function transaction(string $state): Transaction {
        $transaction = new Transaction();
        $reservation = new OpenPaygoIssuanceReservation();
        $reservation->state = $state;
        $transaction->setRelation('openPaygoIssuance', $reservation);
        $transaction->setRelation('device', null)->setRelation('token', null);

        return $transaction;
    }
}
