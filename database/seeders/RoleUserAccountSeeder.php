<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleUserAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $defaults = [
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $roleUserAccounts = [
            [
                'role_id' => 1,
                'role_name' => 'Super Admin',
                'user_account_id' => 1,
                'user_name' => 'Lawrence Agulto'
            ],
        ];

        DB::table('role_user_account')->insert(
            array_map(fn ($row) => $row + $defaults, $roleUserAccounts)
        );
    }
}
