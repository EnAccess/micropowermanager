<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UserSeeder::class);
        $this->call(ConnectionTypeSeeder::class);
        $this->call(ManufacturerSeeder::class);
        $this->call(SubConnectionTypeSeeder::class);
        $this->call(MenuItemsSeeder::class);
        $this->call(SubMenuItemsSeeder::class);
        $this->call(ConnectionGroupSeeder::class);
        $this->call(MainSettingsSeeder::class);
        $this->call(MapSettingsSeeder::class);
        $this->call(TicketSettingsSeeder::class);
        $this->call(SmsBodiesSeeder::class);
        $this->call(SmsRecendInformationKeySeeder::class);
        $this->call(SmsVariableDefaultValuesSeeder::class);
    }
}
