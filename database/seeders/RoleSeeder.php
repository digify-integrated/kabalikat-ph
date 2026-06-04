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

        $defaults = [
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $roles = [
            [
                'role_name' => 'Super Admin',
                'role_description' => 'Has unrestricted access to all modules, settings, and administrative functions, including user and role management.',
            ],
            [
                'role_name' => 'System Admin',
                'role_description' => 'Manages system configuration, user accounts, access permissions, and application maintenance to ensure smooth operation.',
            ],
            [
                'role_name' => 'Cashier',
                'role_description' => 'Processes sales transactions, manages customer payments, handles refunds, and generates daily sales records.',
            ],
            [
                'role_name' => 'Inventory Admin',
                'role_description' => 'Oversees inventory management, including stock monitoring, product updates, stock adjustments, and inventory reporting.',
            ],
        ];

        DB::table('role')->insert(
            array_map(fn ($row) => $row + $defaults, $roles)
        );
    }
}
