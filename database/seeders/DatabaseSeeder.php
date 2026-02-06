<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            AppSeeder::class,
            NavigationMenuSeeder::class,
            SystemActionSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,
            RoleSystemActionPermissionSeeder::class,
            RoleUserAccountSeeder::class,
        ]);
    }
}
