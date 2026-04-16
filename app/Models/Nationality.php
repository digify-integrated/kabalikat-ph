<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nationality extends Model
{
    protected $table = 'nationality';

    protected $fillable = [
        'nationality_name',
        'last_log_by'
    ];
}
