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

    /**
     * A file type has many file extensions.
     */
    public function fileExtensions(): HasMany
    {
        return $this->hasMany(FileExtension::class, 'file_type_id', 'id');
    }
}
