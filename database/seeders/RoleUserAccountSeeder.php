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

        DB::table('role_user_account')->insert([
            'role_id' => 1,
            'user_account_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
