<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogInstance extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'log_input_id',
        'domain_id',
    ];
}
