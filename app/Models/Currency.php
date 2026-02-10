<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Currency extends Model
{
    protected $fillable = ['name', 'code', 'rate'];

    public function databases(): HasMany {
        return $this->hasMany(Database::class);
    }

    public static function getLastUpdate(){
        $currencies = Currency::where('code', '!=', 'EUR')->get();
        $lastUpdatedAt = $currencies->max('updated_at');

        if (!$lastUpdatedAt) return null;

        $lastUpdated = Carbon::parse($lastUpdatedAt);
        $hoursDiff = $lastUpdated->diffInHours(Carbon::now());

        return (float) number_format($hoursDiff, 1);
    }
}
