<?php

namespace App\Http\Traits;

use App\Models\Database;

trait DatabaseTrait
{
    public function regenerateDatabaseConfig()
    {
        $path = config_path('database.php');
        $lines = file($path); // читаем файл в массив строк
        $newContent = "";
        $insideConnections = false;
        $bracketLevel = 0;

        foreach ($lines as $line) {
            // нашли начало блока connections
            if (strpos($line, "'connections' => [") !== false) {
                $newContent .= "    'connections' => [\n";

                // 🚀 вставляем коннекты из БД
                $databases = Database::with('domain')->get();
                foreach ($databases as $db) {
                    $newContent .= "        '{$db->domain->name}' => [\n";
                    $newContent .= "            'driver' => '{$db->driver}',\n";
                    $newContent .= "            'host' => '{$db->host}',\n";
                    $newContent .= "            'port' => '{$db->port}',\n";
                    $newContent .= "            'database' => '{$db->database}',\n";
                    $newContent .= "            'username' => '{$db->username}',\n";
                    $newContent .= "            'password' => '{$db->password}',\n";
                    $newContent .= "            'charset' => 'utf8mb4',\n";
                    $newContent .= "            'collation' => 'utf8mb4_unicode_ci',\n";
                    $newContent .= "            'prefix' => '',\n";
                    $newContent .= "            'strict' => false,\n";
                    $newContent .= "            'engine' => null,\n";
                    $newContent .= "        ],\n";
                }

                // 🚀 добавляем стандартные коннекты
                $newContent .= <<<'PHP'
                    'sqlite' => [
                        'driver' => 'sqlite',
                        'url' => env('DB_URL'),
                        'database' => env('DB_DATABASE', database_path('database.sqlite')),
                        'prefix' => '',
                        'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
                    ],

                    'mysql' => [
                        'driver' => 'mysql',
                        'url' => env('DB_URL'),
                        'host' => env('DB_HOST', '127.0.0.1'),
                        'port' => env('DB_PORT', '3306'),
                        'database' => env('DB_DATABASE', 'laravel'),
                        'username' => env('DB_USERNAME', 'root'),
                        'password' => env('DB_PASSWORD', ''),
                        'unix_socket' => env('DB_SOCKET', ''),
                        'charset' => env('DB_CHARSET', 'utf8mb4'),
                        'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
                        'prefix' => '',
                        'prefix_indexes' => true,
                        'strict' => true,
                        'engine' => null,
                        'options' => extension_loaded('pdo_mysql') ? array_filter([
                            PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                        ]) : [],
                    ],

                    'pgsql' => [
                        'driver' => 'pgsql',
                        'url' => env('DB_URL'),
                        'host' => env('DB_HOST', '127.0.0.1'),
                        'port' => env('DB_PORT', '5432'),
                        'database' => env('DB_DATABASE', 'laravel'),
                        'username' => env('DB_USERNAME', 'root'),
                        'password' => env('DB_PASSWORD', ''),
                        'charset' => env('DB_CHARSET', 'utf8'),
                        'prefix' => '',
                        'prefix_indexes' => true,
                        'search_path' => 'public',
                        'sslmode' => 'prefer',
                    ],

                    'sqlsrv' => [
                        'driver' => 'sqlsrv',
                        'url' => env('DB_URL'),
                        'host' => env('DB_HOST', 'localhost'),
                        'port' => env('DB_PORT', '1433'),
                        'database' => env('DB_DATABASE', 'laravel'),
                        'username' => env('DB_USERNAME', 'root'),
                        'password' => env('DB_PASSWORD', ''),
                        'charset' => env('DB_CHARSET', 'utf8'),
                        'prefix' => '',
                        'prefix_indexes' => true,
                    ],

            PHP;

                $bracketLevel = 1;
                $insideConnections = true;
                continue;
            }

            // пропускаем все строки внутри connections до закрывающей ]
            if ($insideConnections) {
                $bracketLevel += substr_count($line, '[');
                $bracketLevel -= substr_count($line, ']');

                if ($bracketLevel === 0) {
                    $newContent .= "    ],\n"; // закрыли весь блок
                    $insideConnections = false;
                }
                continue;
            }

            // всё остальное копируем без изменений
            $newContent .= $line;
        }

        // перезаписываем файл
        file_put_contents($path, $newContent);
    }

}
