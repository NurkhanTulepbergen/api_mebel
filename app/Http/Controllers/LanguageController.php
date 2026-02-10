<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class LanguageController extends Controller
{
    public $tableName = 'oc_language';
    public $availableDatabases;
    public $database;

    public function __construct(Request $request){
        $availableDatabases =  json_decode(env('ARRAY'));
        $this->database = $request->header('database');
        if (!$this->database || !in_array($this->database, $availableDatabases)) {
            abort(400, "Database header is required. Expected one of the database values: b2b.de, be, de, de2 or lu");
        }
    }

    public function create(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string',
            'code' => 'required|string',
            'locale' => 'required|string',
            'image' => 'required|string',
            'directory' => 'required|string',
            'sort_order' => 'required|integer',
            'status' => 'required|integer',
        ]);

        $languageId = DB::connection($this->database)->table($this->$tableName)->insertGetId($validated);
        $language = DB::connection($this->database)->table($this->tableName)->where('language_id', $languageId)->first();

        return response()->json([
            'message' => 'Язык был создан',
            'data' => $language
        ], 201);
    }

    public function all(Request $request) {
        $languages = DB::connection($this->database)->table($this->tableName)->get();
        return response()->json([
            'data' => $languages
        ], 200);
    }

    public function update(Request $request, int $id) {
        $validated = $request->validate([
            'name' => 'string',
            'code' => 'string',
            'locale' => 'string',
            'image' => 'string',
            'directory' => 'string',
            'sort_order' => 'integer',
            'status' => 'integer',
        ]);

        DB::connection($this->database)->table($this->tableName)->where('language_id', $id)->update($validated);
        $language = DB::connection($this->database)->table($this->tableName)->where('language_id', $id)->first();

        return response()->json([
            'message' => 'Язык #'.$id.' обновлен',
            'data' => $language
        ], 201);
    }

    public function delete(int $id, Request $request) {
        DB::connection($this->database)->table($this->tableName)->where('language_id', $id)->delete();
        return response()->json([
            'message' => 'Язык #'.$id." был удален"
        ], 200);
    }
}
