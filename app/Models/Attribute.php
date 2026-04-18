<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $table = 'attribute';

    protected $fillable = [
        'attribute_name',
        'selection_type',
        'last_log_by'
    ];
}
