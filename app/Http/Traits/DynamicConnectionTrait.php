<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\{
    DB,
    Config,
};

use App\Models\{
    Database,
};

trait DynamicConnectionTrait
{
    public function getDynamicConnection(Database $database) {
        $connectionName = 'dynamic_' . $database->id;

        Config::set("database.connections.$connectionName", [
            'driver' => $database->driver,
            'host' => $database->host,
            'port' => $database->port,
            'database' => $database->database,
            'username' => $database->username,
            'password' => $database->password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);

        DB::purge($connectionName);
        return DB::connection($connectionName);
    }
}
