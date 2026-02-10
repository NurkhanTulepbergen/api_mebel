<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\{
    BelongsToMany,
    HasMany,
};

class Log extends Model
{
    protected $fillable = [
        'status',
        'type',
        'created_at',
        'finished_at',
        'user_id',
    ];

    public function domains(): BelongsToMany {
        return $this->belongsToMany(Domain::class, 'log_domains');
    }

    public function inputs(): HasMany {
        return $this->hasMany(LogInput::class);
    }

}
