<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class  extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::PROSPECT,
                'name' => 'Prospect',
                'description' => 'This plugin developed for data integration with Prospect energy data platform for aggregation, validation and visualization.',
                'tail_tag' => 'Prospect',
                'installation_command' => 'prospect:install',
                'root_class' => 'Prospect',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('mpm_plugins')
            ->where('id', MpmPlugin::PROSPECT)
            ->delete();
    }
};
