<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UploadSettingFileExtension extends Model
{
    protected $table = 'upload_setting_file_extension';

    protected $fillable = [
        'upload_setting_id',
        'upload_setting_name',
        'file_extension_id',
        'file_extension_name',
        'file_extension',
        'last_log_by'
    ];

    public function uploadSetting(): BelongsTo
    {
        return $this->belongsTo(UploadSetting::class, 'upload_setting_id');
    }

    public function fileExtension(): BelongsTo
    {
        return $this->belongsTo(FileExtension::class, 'file_extension_id');
    }

    public function getExtensionAttribute(): string
    {
        return $this->file_extension;
    }

    public function getSettingNameAttribute(): string
    {
        return $this->upload_setting_name;
    }
}