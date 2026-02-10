<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ftp extends Model
{
    protected $fillable = [
        'domain_id',
        'host',
        'port',
        'username',
        'password',
        'path',
        'protocol',
    ];

    protected $casts = [
        'password' => 'encrypted',
    ];

    public function domain(): BelongsTo {
        return $this->belongsTo(Domain::class);
    }
}
