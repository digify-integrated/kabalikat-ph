<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_log';

    protected $fillable = [
        'reference_id',
        'table_name',
        'log',
        'changed_by',
    ];

    protected $casts = [
        'reference_id' => 'integer',
        'changed_by'   => 'integer',
    ];

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
