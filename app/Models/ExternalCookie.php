<?php

namespace App\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Model;

class ExternalCookie extends Model
{
    protected $fillable = ['domain_id', 'content', 'expires_at'];

    public function domain() {
        return $this->belongsTo(Domain::class);
    }

    public static function getLastUpdate(){
        $currencies = ExternalCookie::all();
        $lastCreatedAt = $currencies->max('updated_at');
        if (!$lastCreatedAt) return null;

        $lastCreated = Carbon::parse($lastCreatedAt);
        $daysDiff = $lastCreated->diffInDays(Carbon::now());
        return (float) number_format($daysDiff, 1);
    }

    public static function isOlderThanWeek(){
        return ExternalCookie:: getLastUpdate() > 7;
    }
}
