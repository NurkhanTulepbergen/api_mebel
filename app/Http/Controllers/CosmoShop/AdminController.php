<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public $availableDatabases;
    public $database;
    public function __construct(Request $request){
        $array = env('DB_ARRAY');
        $availableDatabases =  json_decode($array);
        $this->database = $request->header('database');
        if (!$this->database || !in_array($this->database, $availableDatabases)) {
            abort(400, "Database header is required. Expected one of the database values: ".implode(', ', $availableDatabases));
        }
    }
    public function allTablesWithArticleId(Request $request) {
        $validated = $request->validate([
            'article_id' => 'required|integer'
        ]);
        $selects = [
            'article_id' => [
                [
                    'query' => "
                        SELECT table_name
                        FROM information_schema.columns
                        WHERE column_name = 'artikelid'
                    ",
                    'pk' => 'artikelid'
                ],
                [
                    'query' => "
                        SELECT table_name
                        FROM information_schema.columns
                        WHERE column_name = 'artid'
                    ",
                    'pk' => 'artid'
                ],
            ]
        ];
        $output = [];
        $nulls = [];
        $tableNames = [];
        $i = 0;
        foreach($selects as $key => $selectArr) {
            $nulls[$key] = [];
            foreach($selectArr as $select) {
                $tables = DB::connection($this->database)->select($select['query']);
                foreach($tables as $table) {
                    $tableName = $table->table_name;
                    if(!in_array($tableName, $tableNames)) {
                        $item = DB::connection($this->database)
                        ->table($tableName)
                        ->where($select['pk'], $validated[$key])
                        ->get();
                        if($item->count() == 0) $nulls[$key][] = $tableName;
                        else $output[$tableName] = $item;
                        $tableNames[] = $tableName;
                        $i++;
                    }
                }
            }
        }
        // $tables = DB::connection($this->database)->select("
        //     SELECT table_name
        //     FROM information_schema.columns
        //     WHERE column_name = 'artikelid'
        //     AND table_schema = DATABASE()
        // ");
        // $output = [];
        // $nulls = [];
        // foreach($tables as $table) {
        //     $item = DB::connection($this->database)
        //     ->table($table->table_name)
        //     ->where('artikelid', $id)
        //     ->get();
        //     if ($item->count() == 0) $nulls[] = $table->table_name;
        //     else $output[$table->table_name] = $item;
        // }
        return response([
            'count' => $i,
            'found' => $output,
            'not_found' => $nulls
        ]);
    }
}
