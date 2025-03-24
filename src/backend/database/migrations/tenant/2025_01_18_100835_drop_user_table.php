<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        // Fetch records from tenant users table
        $users = DB::connection('tenant')->table('users')->get();

        // Insert users into main schema if the main users table exists
        if (Schema::connection('micro_power_manager')->hasTable('users')) {
            foreach ($users as $user) {
                DB::connection('micro_power_manager')->table('users')->insert([
                    'id' => $user->id,
                    'name' => $user->name,
                    'company_id' => $user->company_id,
                    'email' => $user->email,
                    'password' => $user->password,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]);
            }
        } else {
            throw new Exception('Table `micro_power_manager.users` does not exist. Please make sure the Central Database migration have been run.');
        }

        // Drop the users table in the tenant schema
        Schema::connection('tenant')->dropIfExists('users');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('company_id')->unsigned();
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }
};
