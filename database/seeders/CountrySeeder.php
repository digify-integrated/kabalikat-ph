<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
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

        $country = [
            [
                'country_name' => 'Philippines',
            ],
        ];

        DB::table('country')->insert(
            array_map(fn ($row) => $row + $defaults, $country)
        );
    }
}
