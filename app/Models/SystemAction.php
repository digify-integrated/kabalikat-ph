<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemAction extends Model
{
    protected $table = 'system_action';

    protected $fillable = [
        'system_action_name',
        'system_action_description',
        'last_log_by'
    ];

    public function getLabelAttribute(): string
    {
        return $this->system_action_name;
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('system_action_name');
    }
}
