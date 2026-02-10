<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Database extends Model
{
    protected $fillable = [
        'domain_id',
        'driver',
        'host',
        'port',
        'database',
        'username',
        'password',
    ];

    protected $casts = [
        'password' => 'encrypted',
    ];

    public function domain(): BelongsTo {
        return $this->belongsTo(Domain::class);
    }
}
