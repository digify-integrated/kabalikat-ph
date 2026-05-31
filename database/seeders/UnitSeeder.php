<?php

namespace Database\Seeders;

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
            ['unit_name' => 'Kilogram', 'abbreviation' => 'kg', 'unit_type_id' => '1', 'unit_type_name' => 'Weight/Mass'],
            ['unit_name' => 'Grams', 'abbreviation' => 'g', 'unit_type_id' => '1', 'unit_type_name' => 'Weight/Mass'],
            ['unit_name' => 'Milliliter', 'abbreviation' => 'ml', 'unit_type_id' => '2', 'unit_type_name' => 'Volume/Liquid'],
            ['unit_name' => 'Liter', 'abbreviation' => 'l', 'unit_type_id' => '2', 'unit_type_name' => 'Volume/Liquid'],
            ['unit_name' => 'Gallon', 'abbreviation' => 'gal', 'unit_type_id' => '2', 'unit_type_name' => 'Volume/Liquid'],
            ['unit_name' => 'Cup', 'abbreviation' => 'c', 'unit_type_id' => '2', 'unit_type_name' => 'Volume/Liquid'],
            ['unit_name' => 'Piece', 'abbreviation' => 'pc', 'unit_type_id' => '3', 'unit_type_name' => 'Countable/Packaging Units'],
            ['unit_name' => 'Pack', 'abbreviation' => 'pk', 'unit_type_id' => '3', 'unit_type_name' => 'Countable/Packaging Units'],
            ['unit_name' => 'Portion', 'abbreviation' => 'port', 'unit_type_id' => '3', 'unit_type_name' => 'Countable/Packaging Units'],
            ['unit_name' => 'Slice', 'abbreviation' => 'sl', 'unit_type_id' => '3', 'unit_type_name' => 'Countable/Packaging Units'],
            ['unit_name' => 'Can', 'abbreviation' => 'cn', 'unit_type_id' => '3', 'unit_type_name' => 'Countable/Packaging Units'],
            ['unit_name' => 'Jar', 'abbreviation' => 'jr', 'unit_type_id' => '3', 'unit_type_name' => 'Countable/Packaging Units'],
            ['unit_name' => 'Bottle', 'abbreviation' => 'btl', 'unit_type_id' => '3', 'unit_type_name' => 'Countable/Packaging Units'],
            ['unit_name' => 'Bar', 'abbreviation' => 'br', 'unit_type_id' => '3', 'unit_type_name' => 'Countable/Packaging Units'],
            ['unit_name' => 'Order', 'abbreviation' => 'or', 'unit_type_id' => '3', 'unit_type_name' => 'Countable/Packaging Units'],
        ];

        DB::table('unit')->insert(
            array_map(fn ($row) => $row + $defaults, $unit)
        );
    }
}
