<?php

use App\Models\AssetType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

return  new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection('tenant')->table('asset_types')->where('id', AssetType::APPLIANCE_TYPE_SHS)
            ->update([
                'paygo_enabled' => true,
                'updated_at' => Carbon::now(),
            ]);

        DB::connection('tenant')->table('asset_types')->where('id', AssetType::APPLIANCE_TYPE_E_BIKE)
            ->update([
                'paygo_enabled' => true,
                'updated_at' => Carbon::now(),
            ]);

        DB::connection('tenant')->table('asset_types')->where('id', AssetType::APPLIANCE_TYPE_ELECTRONICS)
            ->update([
                'paygo_enabled' => false,
                'updated_at' => Carbon::now(),
            ]);

        DB::connection('tenant')->table('asset_types')->where('id', AssetType::APPLIANCE_TYPE_GOODS)
            ->update([
                'paygo_enabled' => false,
                'updated_at' => Carbon::now(),
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('tenant')->table('asset_types')->update([
            'paygo_enabled' => false,
            'updated_at' => Carbon::now(),
        ]);
    }
};
