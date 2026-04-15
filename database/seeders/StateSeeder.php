<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StateSeeder extends Seeder
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

        $state = [
            ['state_name' => 'Abra', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Agusan del Norte', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Agusan del Sur', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Aklan', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Albay', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Antique', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Apayao', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Aurora', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Autonomous Region in Muslim Mindanao', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Basilan', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Bataan', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Batanes', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Batangas', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Benguet', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Bicol', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Biliran', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Bohol', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Bukidnon', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Bulacan', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Cagayan', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Cagayan Valley', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Calabarzon', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Camarines Norte', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Camarines Sur', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Camiguin', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Capiz', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Caraga', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Catanduanes', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Cavite', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Cebu', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Central Luzon', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Central Visayas', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Cordillera Administrative', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Cotabato', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Davao', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Davao de Oro', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Davao del Norte', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Davao del Sur', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Davao Occidental', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Davao Oriental', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Dinagat Islands', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Eastern Samar', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Eastern Visayas', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Guimaras', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Ifugao', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Ilocos', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Ilocos Norte', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Ilocos Sur', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Iloilo', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Isabela', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Kalinga', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'La Union', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Laguna', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Lanao del Norte', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Lanao del Sur', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Leyte', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Maguindanao del Norte', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Maguindanao del Sur', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Marinduque', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Masbate', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Mimaropa', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Misamis Occidental', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Misamis Oriental', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Mountain Province', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'National Capital Region (Metro Manila)', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Negros Occidental', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Negros Oriental', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Northern Mindanao', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Northern Samar', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Nueva Ecija', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Nueva Vizcaya', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Occidental Mindoro', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Oriental Mindoro', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Palawan', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Pampanga', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Pangasinan', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Quezon', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Quirino', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Rizal', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Romblon', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Sarangani', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Siquijor', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Soccsksargen', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Sorsogon', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'South Cotabato', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Southern Leyte', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Sultan Kudarat', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Sulu', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Surigao del Norte', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Surigao del Sur', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Tarlac', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Tawi-Tawi', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Western Samar', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Western Visayas', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Zambales', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Zamboanga del Norte', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Zamboanga del Sur', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Zamboanga Peninsula', 'country_id' => 1, 'country_name' => 'Philippines'],
            ['state_name' => 'Zamboanga Sibugay', 'country_id' => 1, 'country_name' => 'Philippines'],
        ];

        DB::table('state')->insert(
            array_map(fn ($row) => $row + $defaults, $state)
        );
    }
}
