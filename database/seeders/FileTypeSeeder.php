<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FileTypeSeeder extends Seeder
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
                'file_type_name' => 'Audio',
            ],
            [
                'file_type_name' => 'Compressed',
            ],
            [
                'file_type_name' => 'Disk and Media',
            ],
            [
                'file_type_name' => 'Data and Database',
            ],
            [
                'file_type_name' => 'Email',
            ],
            [
                'file_type_name' => 'Executable',
            ],
            [
                'file_type_name' => 'Font',
            ],
            [
                'file_type_name' => 'Image',
            ],
            [
                'file_type_name' => 'Internet Related',
            ],
            [
                'file_type_name' => 'Presentation',
            ],
            [
                'file_type_name' => 'Spreadsheet',
            ],
            [
                'file_type_name' => 'System Related',
            ],
            [
                'file_type_name' => 'Video',
            ],
            [
                'file_type_name' => 'Word Processor',
            ],
        ];

        DB::table('file_type')->insert(
            array_map(fn ($row) => $row + $defaults, $apps)
        );
    }
}
