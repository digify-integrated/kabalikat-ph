<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        DB::table('role')->insert([
            'role_name'             => 'Super Admin',
            'role_description'      => 'Has full access to all features and settings of the application.',
            'created_at'            => $now,
            'updated_at'            => $now,
        ]);

        DB::table('role')->insert([
            'role_name'             => 'System Admin',
            'role_description'      => 'Responsible for managing system settings, user accounts, and overall maintenance of the application.',
            'created_at'            => $now,
            'updated_at'            => $now,
        ]);
    }
}
