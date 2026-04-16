<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
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

        $currency = [
            [
                'currency_name' => 'Philippine Peso',
                'symbol' => '₱',
                'shorthand' => 'PHP',
            ],
            [
                'currency_name' => 'United States Dollar',
                'symbol' => '$',
                'shorthand' => 'USD',
            ],
            [
                'currency_name' => 'Japanese Yen',
                'symbol' => '¥',
                'shorthand' => 'JPY',
            ],
            [
                'currency_name' => 'South Korean Won',
                'symbol' => '₩',
                'shorthand' => 'KRW',
            ],
            [
                'currency_name' => 'Euro',
                'symbol' => '€',
                'shorthand' => 'EUR',
            ],
            [
                'currency_name' => 'Pound Sterling',
                'symbol' => '£',
                'shorthand' => 'GBP',
            ],
        ];

        DB::table('currency')->insert(
            array_map(fn ($row) => $row + $defaults, $currency)
        );
    }
}
