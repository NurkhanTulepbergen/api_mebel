<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefreshToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'user_agent',
        'ip_address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public $timestamps = true;
}
