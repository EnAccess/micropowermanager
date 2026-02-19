<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Removes orphaned device-owned addresses that no longer
     * have any geographical information attached.
     */
    public function up(): void {
        $orphanAddressIds = DB::connection('tenant')
            ->table('addresses as addr')
            ->leftJoin('geographical_informations as geo', function ($join) {
                $join->on('geo.owner_id', '=', 'addr.id')
                    ->where('geo.owner_type', '=', 'address');
            })
            ->where('addr.owner_type', '=', 'device')
            ->whereNull('geo.id')
            ->pluck('addr.id');

        if ($orphanAddressIds->isNotEmpty()) {
            DB::connection('tenant')
                ->table('addresses')
                ->whereIn('id', $orphanAddressIds)
                ->delete();
        }
    }

    /**
     * Reverse the migrations.
     *
     * This migration performs a data cleanup and is not reversible.
     */
    public function down(): void {}
};
