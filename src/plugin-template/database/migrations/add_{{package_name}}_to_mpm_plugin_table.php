<?php

use App\Models\MpmPlugin;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::table('mpm_plugins')->insert([
            [
                'id' => MpmPlugin::{{constantName}},
                'name' => '{{Package-Name}}',
                'description' => '{{description}}',
                'tail_tag' => '{{Package-Name}}',
                'installation_command' => '{{package-name}}:install',
                'root_class' => '{{Package-Name}}',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function down(): void {
        DB::table('mpm_plugins')->where('id', MpmPlugin::{{constantName}})->delete();
    }
};
