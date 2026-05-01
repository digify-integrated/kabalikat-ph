<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FileExtension extends Model
{
    protected $table = 'file_extension';

    protected $fillable = [
        'file_extension_name',
        'file_extension',
        'file_type_id',
        'file_type_name',
        'last_log_by'
    ];

    public function fileType(): BelongsTo
    {
        return $this->belongsTo(FileType::class, 'file_type_id', 'id');
    }

    public function uploadSettings(): BelongsToMany
    {
        return $this->belongsToMany(
            UploadSetting::class,
            'upload_setting_file_extension',
            'file_extension_id',
            'upload_setting_id'
        )->withTimestamps();
    }

    public function uploadSettingLinks(): HasMany
    {
        return $this->hasMany(
            UploadSettingFileExtension::class,
            'file_extension_id',
            'id'
        );
    }

    public function getFormattedExtensionAttribute(): string
    {
        return '.' . ltrim($this->file_extension, '.');
    }

    public function isImage(): bool
    {
        return in_array(strtolower($this->file_extension), [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'
        ]);
    }

    public function isDocument(): bool
    {
        return in_array(strtolower($this->file_extension), [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'
        ]);
    }

    public function scopeByType($query, int $fileTypeId)
    {
        return $query->where('file_type_id', $fileTypeId);
    }
}
