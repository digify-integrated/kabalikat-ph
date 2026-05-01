<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FileType extends Model
{
    protected $table = 'file_type';

    protected $fillable = [
        'file_type_name',
        'last_log_by'
    ];

    public function fileExtensions(): HasMany
    {
        return $this->hasMany(FileExtension::class, 'file_type_id', 'id');
    }

    public function hasExtensions(): bool
    {
        return $this->fileExtensions()->exists();
    }

    public function getExtensionCountAttribute(): int
    {
        return $this->fileExtensions()->count();
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->file_type_name;
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('file_type_name');
    }
}
