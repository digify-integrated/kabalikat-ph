<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $defaults = [
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $unit = [
            ['unit_name' => 'Piece', 'abbreviation' => 'pc', 'unit_type_id' => '1', 'unit_type_name' => 'Quantity/Count'],
            ['unit_name' => 'Capsule', 'abbreviation' => 'cap', 'unit_type_id' => '1', 'unit_type_name' => 'Quantity/Count'],
            ['unit_name' => 'Tablet', 'abbreviation' => 'tab', 'unit_type_id' => '1', 'unit_type_name' => 'Quantity/Count'],
            ['unit_name' => 'Ream', 'abbreviation' => 'rm', 'unit_type_id' => '1', 'unit_type_name' => 'Quantity/Count'],

            ['unit_name' => 'Milligram', 'abbreviation' => 'mg', 'unit_type_id' => '2', 'unit_type_name' => 'Weight/Mass'],
            ['unit_name' => 'Kilogram', 'abbreviation' => 'kg', 'unit_type_id' => '2', 'unit_type_name' => 'Weight/Mass'],
            ['unit_name' => 'Gram', 'abbreviation' => 'g', 'unit_type_id' => '2', 'unit_type_name' => 'Weight/Mass'],

            ['unit_name' => 'Milliliter', 'abbreviation' => 'ml', 'unit_type_id' => '3', 'unit_type_name' => 'Volume/Liquid'],
            ['unit_name' => 'Liter', 'abbreviation' => 'L', 'unit_type_id' => '3', 'unit_type_name' => 'Volume/Liquid'],
            
            ['unit_name' => 'Meter', 'abbreviation' => 'm', 'unit_type_id' => '4', 'unit_type_name' => 'Length/Area'],
            
            ['unit_name' => 'Hour', 'abbreviation' => 'hr', 'unit_type_id' => '5', 'unit_type_name' => 'Time/Duration'],
            ['unit_name' => 'Session', 'abbreviation' => 'sess', 'unit_type_id' => '5', 'unit_type_name' => 'Time/Duration'],
        ];

        DB::table('unit')->insert(
            array_map(fn ($row) => $row + $defaults, $unit)
        );
    }
}
