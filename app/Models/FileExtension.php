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

    /**
     * A file extension belongs to a file type.
     */
    public function fileType(): BelongsTo
    {
        return $this->belongsTo(FileType::class, 'file_type_id', 'id');
    }

    /**
     * A file extension can be linked to many upload settings via the pivot table.
     */
    public function uploadSettings(): BelongsToMany
    {
        return $this->belongsToMany(
            UploadSetting::class,
            'upload_setting_file_extension',
            'file_extension_id',
            'upload_setting_id'
        )->withTimestamps();
    }

    /**
     * If you want direct access to pivot rows as a model.
     */
    public function uploadSettingFileExtensions(): HasMany
    {
        return $this->hasMany(UploadSettingFileExtension::class, 'file_extension_id', 'id');
    }
}
