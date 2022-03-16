<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $randomPassword = str_random(8);
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make($randomPassword),
        ]);

        $this->command->alert('
        Please use following credentials to login:
        Email = admin@admin.com
        Password = '. $randomPassword
        );
    }
}
