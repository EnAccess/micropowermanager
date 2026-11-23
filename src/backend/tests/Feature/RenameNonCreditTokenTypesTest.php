<?php

namespace Tests\Feature;

use App\Enums\DeviceType;
use App\Models\Token;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RenameNonCreditTokenTypesTest extends TestCase {
    private int $lastTransactionId = 0;

    public function testItRenamesTheStoredNonCreditTokenTypes(): void {
        $solarHomeSystem = $this->insertDevice(DeviceType::SolarHomeSystem);
        $meter = $this->insertDevice(DeviceType::Meter);

        $unlock = $this->insertToken($solarHomeSystem, 'unlock');
        $solarHomeSystemReset = $this->insertToken($solarHomeSystem, 'reset');
        $meterReset = $this->insertToken($meter, 'reset');
        $energy = $this->insertToken($meter, Token::TYPE_ENERGY);

        $this->runMigration();

        $types = DB::connection('tenant')->table('tokens')
            ->whereIn('id', [$unlock, $solarHomeSystemReset, $meterReset, $energy])
            ->pluck('token_type', 'id');

        $this->assertSame(Token::TYPE_UNLOCK_PAYGO, $types[$unlock]);
        $this->assertSame(Token::TYPE_RESET_CREDIT, $types[$solarHomeSystemReset]);
        $this->assertSame(Token::TYPE_CLEAR_CREDIT, $types[$meterReset]);
        $this->assertSame(Token::TYPE_ENERGY, $types[$energy]);
    }

    private function insertDevice(DeviceType $type): int {
        return DB::connection('tenant')->table('devices')->insertGetId([
            'device_type' => $type->value,
            'device_id' => 1,
            'device_serial' => uniqid('SERIAL-'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertToken(int $deviceId, string $tokenType): int {
        return DB::connection('tenant')->table('tokens')->insertGetId([
            'token' => uniqid(),
            'token_type' => $tokenType,
            'device_id' => $deviceId,
            'transaction_id' => ++$this->lastTransactionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function runMigration(): void {
        $migration = require database_path('migrations/tenant/2026_11_23_000000_rename_non_credit_token_types.php');
        $migration->up();
    }
}
