<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCredential extends Model
{
    protected $casts = [
        'password' => 'encrypted',
    ];

    protected $fillable = ['domain_id', 'username', 'password'];

    public function domain() {
        return $this->belongsTo(Domain::class);
    }
}
