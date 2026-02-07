<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
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

        $users = [
            [
                'email' => 'ldagulto@gmail.com',
                'name' => 'Lawrence Agulto',
                'password' => Hash::make('password123'),
                'status' => 'Active',
            ],
        ];

        DB::table('users')->insert(
            array_map(fn ($row) => $row + $defaults, $users)
        );

        // Additional random users
        //User::factory(10)->create();
    }
}
