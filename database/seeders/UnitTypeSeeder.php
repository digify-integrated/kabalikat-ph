<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitTypeSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $unitType = [
            ['unit_type_name' => 'Quantity/Count'],
            ['unit_type_name' => 'Weight/Mass'],
            ['unit_type_name' => 'Volume/Liquid'],
            ['unit_type_name' => 'Length/Area'],
            ['unit_type_name' => 'Time/Duration'],
        ];

        DB::table('unit_type')->insert(
            array_map(fn ($row) => $row + $defaults, $unitType)
        );
    }
}
