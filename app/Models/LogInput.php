<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogInput extends Model
{
    protected $fillable = [
        'log_id',
        'key_type',
        'key',
        'provided_value',
    ];

    public function log() {
        return $this->belongsTo(Log::class);
    }

    public function instances() {
        return $this->belongsToMany(LogInstance::class);
    }
}
