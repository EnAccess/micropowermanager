<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MainSettings;
use Database\Factories\MainSettingsFactory;
use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReferencePluginsBySlugMigrationTest extends TestCase {
    /** @var array<string, list<int>> */
    private array $insertedIds = [];

    protected function tearDown(): void {
        foreach ($this->insertedIds as $table => $ids) {
            $this->tenant()->table($table)->whereIn('id', $ids)->delete();
        }

        parent::tearDown();
    }

    public function testItReplacesPluginIdsWithSlugs(): void {
        $mainSettings = MainSettings::query()->first() ?? MainSettingsFactory::new()->create();
        $previousSmsGateway = $mainSettings->getRawOriginal('sms_gateway');
        $migration = $this->migration();

        // The migration changes the schema, which MySQL commits implicitly, so the rows
        // written here are not rolled back and are removed again in tearDown.
        $migration->down();
        try {
            $stsPluginId = $this->insert('plugins', ['mpm_plugin_id' => 33, 'status' => 1]);
            $vodacomPluginId = $this->insert('plugins', ['mpm_plugin_id' => 19, 'status' => 0]);
            $unknownPluginId = $this->insert('plugins', ['mpm_plugin_id' => 99, 'status' => 1]);
            $this->tenant()->table('main_settings')->where('id', $mainSettings->id)->update(['sms_gateway_id' => 26]);
            $deviceId = $this->insert('devices', [
                'device_type' => 'meter',
                'device_id' => 1,
                'device_serial' => 'SLUG-MIGRATION-'.Str::random(8),
                'vending_plugin_id' => 3,
            ]);
            $africasTalkingSmsId = $this->insertSms(18);
            $removedGatewaySmsId = $this->insertSms(2);
            $tailIds = [
                $this->insert('registration_tail', ['component' => 'Settings', 'adjusted' => false]),
                $this->insert('registration_tail', ['component' => 'SparkMeter', 'adjusted' => false]),
                $this->insert('registration_tail', ['component' => 'VodacomMobileMoney', 'adjusted' => true]),
                $this->insert('registration_tail', ['component' => 'Ecreee E-Tender', 'adjusted' => false]),
            ];
        } finally {
            $migration->up();
        }

        try {
            $this->assertSame('sts_meter_vend', $this->value('plugins', $stsPluginId, 'mpm_plugin'));
            $this->assertSame('vodacom_mz_payment_provider', $this->value('plugins', $vodacomPluginId, 'mpm_plugin'));
            $this->assertFalse($this->tenant()->table('plugins')->where('id', $unknownPluginId)->exists());
            $this->assertSame('textbee_sms_gateway', $this->value('main_settings', $mainSettings->id, 'sms_gateway'));
            $this->assertSame('calin_meter', $this->value('devices', $deviceId, 'vending_plugin'));
            $this->assertSame('africas_talking', $this->value('sms', $africasTalkingSmsId, 'gateway'));
            $this->assertNull($this->value('sms', $removedGatewaySmsId, 'gateway'));
            $this->assertSame(
                ['Settings', 'spark_meter', 'vodacom_mz_payment_provider', 'ecreee_e_tender'],
                $this->tenant()->table('registration_tail')->whereIn('id', $tailIds)->orderBy('id')->pluck('component')->all(),
            );
        } finally {
            $this->tenant()->table('main_settings')->where('id', $mainSettings->id)->update(['sms_gateway' => $previousSmsGateway]);
        }
    }

    private function migration(): Migration {
        return require database_path('migrations/tenant/2026_11_24_000000_reference_plugins_by_slug.php');
    }

    private function tenant(): Connection {
        return DB::connection('tenant');
    }

    /**
     * @param array<string, mixed> $row
     */
    private function insert(string $table, array $row): int {
        $id = $this->tenant()->table($table)->insertGetId($row + ['created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        $this->insertedIds[$table][] = $id;

        return $id;
    }

    private function insertSms(int $gatewayId): int {
        return $this->insert('sms', [
            'receiver' => '+255700000000',
            'body' => 'test',
            'status' => 1,
            'direction' => 1,
            'gateway_id' => $gatewayId,
        ]);
    }

    private function value(string $table, int $id, string $column): mixed {
        return $this->tenant()->table($table)->where('id', $id)->value($column);
    }
}
