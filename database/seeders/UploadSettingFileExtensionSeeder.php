<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UploadSettingFileExtensionSeeder extends Seeder
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
                'upload_setting_id' => 1,
                'upload_setting_name' => 'App Logo',
                'file_extension_id' => 63,
                'file_extension_name' => 'PNG',
                'file_extension' => 'png',
            ],
            [
                'upload_setting_id' => 1,
                'upload_setting_name' => 'App Logo',
                'file_extension_id' => 61,
                'file_extension_name' => 'JPG',
                'file_extension' => 'jpg',
            ],
            [
                'upload_setting_id' => 1,
                'upload_setting_name' => 'App Logo',
                'file_extension_id' => 62,
                'file_extension_name' => 'JPEG',
                'file_extension' => 'jpeg',
            ],
            [
                'upload_setting_id' => 2,
                'upload_setting_name' => 'Internal Notes Attachment',
                'file_extension_id' => 63,
                'file_extension_name' => 'PNG',
                'file_extension' => 'png',
            ],
            [
                'upload_setting_id' => 2,
                'upload_setting_name' => 'Internal Notes Attachment',
                'file_extension_id' => 61,
                'file_extension_name' => 'JPG',
                'file_extension' => 'jpg',
            ],
            [
                'upload_setting_id' => 2,
                'upload_setting_name' => 'Internal Notes Attachment',
                'file_extension_id' => 62,
                'file_extension_name' => 'JPEG',
                'file_extension' => 'jpeg',
            ],
            [
                'upload_setting_id' => 2,
                'upload_setting_name' => 'Internal Notes Attachment',
                'file_extension_id' => 127,
                'file_extension_name' => 'PDF',
                'file_extension' => 'pdf',
            ],
            [
                'upload_setting_id' => 2,
                'upload_setting_name' => 'Internal Notes Attachment',
                'file_extension_id' => 125,
                'file_extension_name' => 'DOC',
                'file_extension' => 'doc',
            ],
            [
                'upload_setting_id' => 2,
                'upload_setting_name' => 'Internal Notes Attachment',
                'file_extension_id' => 125,
                'file_extension_name' => 'DOCX',
                'file_extension' => 'docx',
            ],
            [
                'upload_setting_id' => 2,
                'upload_setting_name' => 'Internal Notes Attachment',
                'file_extension_id' => 130,
                'file_extension_name' => 'TXT',
                'file_extension' => 'txt',
            ],
            [
                'upload_setting_id' => 2,
                'upload_setting_name' => 'Internal Notes Attachment',
                'file_extension_id' => 92,
                'file_extension_name' => 'XLS',
                'file_extension' => 'xls',
            ],
            [
                'upload_setting_id' => 2,
                'upload_setting_name' => 'Internal Notes Attachment',
                'file_extension_id' => 94,
                'file_extension_name' => 'XLSX',
                'file_extension' => 'xlsx',
            ],
            [
                'upload_setting_id' => 2,
                'upload_setting_name' => 'Internal Notes Attachment',
                'file_extension_id' => 89,
                'file_extension_name' => 'PPT',
                'file_extension' => 'ppt',
            ],
            [
                'upload_setting_id' => 2,
                'upload_setting_name' => 'Internal Notes Attachment',
                'file_extension_id' => 90,
                'file_extension_name' => 'PPTX',
                'file_extension' => 'pptx',
            ],
            [
                'upload_setting_id' => 3,
                'upload_setting_name' => 'Import File',
                'file_extension_id' => 25,
                'file_extension_name' => 'CSV',
                'file_extension' => 'csv',
            ],
            [
                'upload_setting_id' => 4,
                'upload_setting_name' => 'User Account Profile Picture',
                'file_extension_id' => 63,
                'file_extension_name' => 'PNG',
                'file_extension' => 'png',
            ],
            [
                'upload_setting_id' => 4,
                'upload_setting_name' => 'User Account Profile Picture',
                'file_extension_id' => 61,
                'file_extension_name' => 'JPG',
                'file_extension' => 'jpg',
            ],
            [
                'upload_setting_id' => 4,
                'upload_setting_name' => 'User Account Profile Picture',
                'file_extension_id' => 62,
                'file_extension_name' => 'JPEG',
                'file_extension' => 'jpeg',
            ],
            [
                'upload_setting_id' => 5,
                'upload_setting_name' => 'Company Logo',
                'file_extension_id' => 63,
                'file_extension_name' => 'PNG',
                'file_extension' => 'png',
            ],
            [
                'upload_setting_id' => 5,
                'upload_setting_name' => 'Company Logo',
                'file_extension_id' => 61,
                'file_extension_name' => 'JPG',
                'file_extension' => 'jpg',
            ],
            [
                'upload_setting_id' => 5,
                'upload_setting_name' => 'Company Logo',
                'file_extension_id' => 62,
                'file_extension_name' => 'JPEG',
                'file_extension' => 'jpeg',
            ],
            [
                'upload_setting_id' => 6,
                'upload_setting_name' => 'Employee Image',
                'file_extension_id' => 63,
                'file_extension_name' => 'PNG',
                'file_extension' => 'png',
            ],
            [
                'upload_setting_id' => 6,
                'upload_setting_name' => 'Employee Image',
                'file_extension_id' => 61,
                'file_extension_name' => 'JPG',
                'file_extension' => 'jpg',
            ],
            [
                'upload_setting_id' => 6,
                'upload_setting_name' => 'Employee Image',
                'file_extension_id' => 62,
                'file_extension_name' => 'JPEG',
                'file_extension' => 'jpeg',
            ],
            [
                'upload_setting_id' => 7,
                'upload_setting_name' => 'Employee Document',
                'file_extension_id' => 63,
                'file_extension_name' => 'PNG',
                'file_extension' => 'png',
            ],
            [
                'upload_setting_id' => 7,
                'upload_setting_name' => 'Employee Document',
                'file_extension_id' => 61,
                'file_extension_name' => 'JPG',
                'file_extension' => 'jpg',
            ],
            [
                'upload_setting_id' => 7,
                'upload_setting_name' => 'Employee Document',
                'file_extension_id' => 62,
                'file_extension_name' => 'JPEG',
                'file_extension' => 'jpeg',
            ],
            [
                'upload_setting_id' => 7,
                'upload_setting_name' => 'Employee Document',
                'file_extension_id' => 127,
                'file_extension_name' => 'PDF',
                'file_extension' => 'pdf',
            ],
            [
                'upload_setting_id' => 7,
                'upload_setting_name' => 'Employee Document',
                'file_extension_id' => 125,
                'file_extension_name' => 'DOC',
                'file_extension' => 'doc',
            ],
            [
                'upload_setting_id' => 7,
                'upload_setting_name' => 'Employee Document',
                'file_extension_id' => 125,
                'file_extension_name' => 'DOCX',
                'file_extension' => 'docx',
            ],
            [
                'upload_setting_id' => 7,
                'upload_setting_name' => 'Employee Document',
                'file_extension_id' => 130,
                'file_extension_name' => 'TXT',
                'file_extension' => 'txt',
            ],
            [
                'upload_setting_id' => 7,
                'upload_setting_name' => 'Employee Document',
                'file_extension_id' => 92,
                'file_extension_name' => 'XLS',
                'file_extension' => 'xls',
            ],
            [
                'upload_setting_id' => 7,
                'upload_setting_name' => 'Employee Document',
                'file_extension_id' => 94,
                'file_extension_name' => 'XLSX',
                'file_extension' => 'xlsx',
            ],
            [
                'upload_setting_id' => 7,
                'upload_setting_name' => 'Employee Document',
                'file_extension_id' => 89,
                'file_extension_name' => 'PPT',
                'file_extension' => 'ppt',
            ],
            [
                'upload_setting_id' => 7,
                'upload_setting_name' => 'Employee Document',
                'file_extension_id' => 90,
                'file_extension_name' => 'PPTX',
                'file_extension' => 'pptx',
            ],
            [
                'upload_setting_id' => 8,
                'upload_setting_name' => 'Product Image',
                'file_extension_id' => 62,
                'file_extension_name' => 'JPEG',
                'file_extension' => 'jpeg',
            ],
            [
                'upload_setting_id' => 8,
                'upload_setting_name' => 'Product Image',
                'file_extension_id' => 61,
                'file_extension_name' => 'JPG',
                'file_extension' => 'jpg',
            ],
            [
                'upload_setting_id' => 8,
                'upload_setting_name' => 'Product Image',
                'file_extension_id' => 63,
                'file_extension_name' => 'PNG',
                'file_extension' => 'png',
            ],
        ];

        DB::table('upload_setting_file_extension')->insert(
            array_map(fn ($row) => $row + $defaults, $apps)
        );
    }
}
