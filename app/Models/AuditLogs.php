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
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function getDecodedLog(): mixed
    {
        return json_decode($this->log, true);
    }

    public function scopeForTable($query, string $table)
    {
        return $query->where('table_name', $table);
    }

    public function scopeForReference($query, int $id)
    {
        return $query->where('reference_id', $id);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('changed_by', $userId);
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at');
    }
}
