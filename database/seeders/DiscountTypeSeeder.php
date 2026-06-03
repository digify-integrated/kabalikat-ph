<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiscountTypeSeeder extends Seeder
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

        $apps = [
            [
                'discount_type_name' => 'Senior Citizen/PWD',
                'value_type' => 'Percentage',
                'discount_value' => '20',
                'is_variable' => 'No',
                'application_order' => 'Before Tax',
                'is_vat_exempt' => 'Yes',
            ],
        ];

        DB::table('discount_type')->insert(
            array_map(fn ($row) => $row + $defaults, $apps)
        );
    }
}
