<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'company';
    
    protected $fillable = [
        'company_name',
        'company_logo',
        'address',
        'city_id',
        'city_name',
        'state_id',
        'state_name',
        'country_id',
        'country_name',
        'tax_id',
        'currency_id',
        'currency_name',
        'phone',
        'telephone',
        'email',
        'website',
        'last_log_by'
    ];

}
