<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UploadSetting extends Model
{
    protected $table = 'upload_setting';

    protected $fillable = [
        'upload_setting_name',
        'upload_setting_description',
        'max_file_size',
        'last_log_by'
    ];

    /**
     * An upload setting can allow many file extensions via the pivot table.
     */
    public function fileExtensions(): BelongsToMany
    {
        return $this->belongsToMany(
            FileExtension::class,
            'upload_setting_file_extension',
            'upload_setting_id',
            'file_extension_id'
        )->withTimestamps();
    }

    /**
     * If you want direct access to pivot rows as a model.
     */
    public function uploadSettingFileExtensions(): HasMany
    {
        return $this->hasMany(UploadSettingFileExtension::class, 'upload_setting_id', 'id');
    }
}
