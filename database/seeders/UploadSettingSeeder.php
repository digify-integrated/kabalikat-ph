<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UploadSettingSeeder extends Seeder
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
                'upload_setting_name' => 'App Logo',
                'upload_setting_description' => 'Sets the upload setting when uploading app logo',
                'max_file_size' => '500',
            ],
            [
                'upload_setting_name' => 'Internal Notes Attachment',
                'upload_setting_description' => 'Sets the upload setting when uploading internal notes attachement.',
                'max_file_size' => '500',
            ],
            [
                'upload_setting_name' => 'Import File',
                'upload_setting_description' => 'Sets the upload setting when importing data.',
                'max_file_size' => '500',
            ],
            [
                'upload_setting_name' => 'User Account Profile Picture',
                'upload_setting_description' => 'Sets the upload setting when uploading user account profile picture.',
                'max_file_size' => '500',
            ],
            [
                'upload_setting_name' => 'Company Logo',
                'upload_setting_description' => 'Sets the upload setting when uploading company logo.',
                'max_file_size' => '500',
            ],
            [
                'upload_setting_name' => 'Employee Image',
                'upload_setting_description' => 'Sets the upload setting when uploading employee image.',
                'max_file_size' => '500',
            ],
            [
                'upload_setting_name' => 'Employee Document',
                'upload_setting_description' => 'Sets the upload setting when uploading employee document.',
                'max_file_size' => '500',
            ],
            [
                'upload_setting_name' => 'Product Image',
                'upload_setting_description' => 'Sets the upload setting when uploading product image.',
                'max_file_size' => '500',
            ],
        ];

        DB::table('upload_setting')->insert(
            array_map(fn ($row) => $row + $defaults, $apps)
        );
    }
}
