<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{
    HasMany,
    HasOne,
    BelongsTo,
    BelongsToMany,
};

class Domain extends Model
{
    protected $fillable = ['name', 'link'];

    public static function getJv() {
        return Domain::with('userCredentials', 'cookies')
            ->where('name', 'LIKE', 'jv.%')
            ->get();
    }

    public static function getXl() {
        return Domain::with('database', 'ftp')
            ->where('link', '!=', null)
            ->where('name', 'NOT LIKE', 'jv.%')
            ->get();
    }

    public static function getByName($name) {
        return Domain::with('database')
            ->where('name',  $name)
            ->first();
    }

    public static function getSeparatedDomains(): array
    {
        return Domain::where('link', '!=', null)->get()->reduce(
            function ($carry, $domain) {
                if (str_contains($domain->name, 'jv')) {
                    $carry['jv'][] = $domain;
                } else {
                    $carry['xl'][] = $domain;
                }
                return $carry;
            },
            ['jv' => [], 'xl' => []]
        );
}

    public function database(): HasOne
    {
        return $this->hasOne(Database::class);
    }
    public function ftp(): HasOne
    {
        return $this->hasOne(Ftp::class);
    }
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
    public function cookies(): HasOne
    {
        return $this->hasOne(ExternalCookie::class);
    }
    public function userCredentials(): HasOne
    {
        return $this->hasOne(UserCredential::class);
    }

    public function logInstances(): BelongsToMany {
        return $this->belongsToMany(LogInstance::class);
    }

}
