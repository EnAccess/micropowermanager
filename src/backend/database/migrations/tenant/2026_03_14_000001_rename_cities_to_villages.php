<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        $schema = Schema::connection('tenant');

        if ($schema->hasTable('cities') && !$schema->hasTable('villages')) {
            Schema::connection('tenant')->rename('cities', 'villages');
        }

        if ($schema->hasTable('addresses') && $schema->hasColumn('addresses', 'city_id') && !$schema->hasColumn('addresses', 'village_id')) {
            Schema::connection('tenant')->table('addresses', function (Blueprint $table): void {
                $table->renameColumn('city_id', 'village_id');
            });
        }

        if ($schema->hasTable('targets') && $schema->hasColumn('targets', 'city_id') && !$schema->hasColumn('targets', 'village_id')) {
            Schema::connection('tenant')->table('targets', function (Blueprint $table): void {
                $table->renameColumn('city_id', 'village_id');
            });
        }

        if ($schema->hasTable('geographical_informations')) {
            DB::connection('tenant')
                ->table('geographical_informations')
                ->where('owner_type', 'city')
                ->update(['owner_type' => 'village']);
        }
    }

    public function down(): void {
        $schema = Schema::connection('tenant');

        if ($schema->hasTable('targets') && $schema->hasColumn('targets', 'village_id') && !$schema->hasColumn('targets', 'city_id')) {
            Schema::connection('tenant')->table('targets', function (Blueprint $table): void {
                $table->renameColumn('village_id', 'city_id');
            });
        }

        if ($schema->hasTable('addresses') && $schema->hasColumn('addresses', 'village_id') && !$schema->hasColumn('addresses', 'city_id')) {
            Schema::connection('tenant')->table('addresses', function (Blueprint $table): void {
                $table->renameColumn('village_id', 'city_id');
            });
        }

        if ($schema->hasTable('villages') && !$schema->hasTable('cities')) {
            Schema::connection('tenant')->rename('villages', 'cities');
        }

        if ($schema->hasTable('geographical_informations')) {
            DB::connection('tenant')
                ->table('geographical_informations')
                ->where('owner_type', 'village')
                ->update(['owner_type' => 'city']);
        }
    }
};
