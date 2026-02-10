<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class ProductDescriptionController extends Controller
{
    public $tableName = 'oc_product';
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


    public function create(Request $request) {
        $validated = $request->validate([
            'product_id'        => 'required|integer',
            'language_id'       => 'required|integer',
            'name'              => 'required|string',
            'description'       => 'required|string',
            'tag'               => 'required|string',
            'meta_title'        => 'required|string',
            'meta_description'  => 'required|string',
            'meta_keyword'      => 'required|string',
        ]);
        DB::connection($this->database)->table($this->tableName)->insertGetId($validated);
        $description = DB::connection($this->database)->table($this->tableName)->where('product_id', $validated['product_id'])->first();

        return response()->json([
            'message' => 'Описание продукта было создано',
            'data' => $description
        ], 201);
    }

    public function read(int $id, Request $request) {
        $description = DB::connection($this->database)
            ->table($this->tableName)
            ->first();
        return response()->json([
            'message' => 'Описание #'.$id,
            'data' => $description
        ], 200);
    }

    public function paginate(Request $request) {
        $descriptions = DB::connection($this->database)->table($this->tableName)->paginate(10);
        return response()->json([
            'data' => $descriptions
        ], 200);
    }

    public function update(Request $request, int $id) {
        $validated = $request->validate([
            'product_id'        => 'integer',
            'language_id'       => 'integer',
            'name'              => 'string',
            'description'       => 'string',
            'tag'               => 'string',
            'meta_title'        => 'string',
            'meta_description'  => 'string',
            'meta_keyword'      => 'string',
        ]);
        DB::connection($this->database)->table($this->tableName)->where('product_id', $id)->update($validated);
        $description = DB::connection($this->database)->table($this->tableName)->where('product_id', $id)->first();

        return response()->json([
            'message' => 'Описание продукта #'.$id.' было обновлено',
            'data' => $description
        ], 201);
    }

    public function delete(int $id, Request $request) {
        DB::connection($this->database)->table($this->tableName)->where('product_id', $id)->delete();
        return response()->json([
            'message' => 'Описание продукта #'.$id." было удалено"
        ], 200);
    }
}
