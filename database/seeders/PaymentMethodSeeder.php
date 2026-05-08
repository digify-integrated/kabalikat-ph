<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $defaults = [
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $apps = [
            [
                'payment_method_name' => 'Cash',
            ],
            [
                'payment_method_name' => 'Credit Card',
            ],
            [
                'payment_method_name' => 'Debit Card',
            ],
            [
                'payment_method_name' => 'Bank Transfer',
            ],
            [
                'payment_method_name' => 'GCash',
            ],
            [
                'payment_method_name' => 'PayMaya',
            ],
            [
                'payment_method_name' => 'GrabPay',
            ],
            [
                'payment_method_name' => 'Check',
            ],
        ];

        DB::table('payment_method')->insert(
            array_map(fn ($row) => $row + $defaults, $apps)
        );
    }
}
