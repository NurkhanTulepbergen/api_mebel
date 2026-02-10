<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class ProductToCategoryController extends Controller
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
            'product_id'    => 'required|integer',
            'category_id'   => 'required|integer',
            'main_category' => 'required|bool',
        ]);
        DB::connection($this->database)->table($this->tableName)->insertGetId($validated);
        $productToCategory = DB::connection($this->database)->table($this->tableName)->where('product_id', $validated['product_id'])->first();

        return response()->json([
            'message' => 'Продукт-Категория была создана',
            'data' => $productToCategory
        ], 201);
    }

    public function read(int $id, Request $request) {
        $productToCategory = DB::connection($this->database)
            ->table($this->tableName)
            ->first();
        return response()->json([
            'message' => 'Продукт-Категория #'.$id,
            'data' => $productToCategory
        ], 200);
    }

    public function all(Request $request) {
        $productToCategory = DB::connection($this->database)->table($this->tableName)->paginate(25);
        return response()->json([
            'data' => $productToCategory
        ], 200);
    }

    public function update(Request $request, int $id) {
        $validated = $request->validate([
            'category_id'   => 'integer',
            'main_category' => 'bool',
        ]);
        DB::connection($this->database)->table($this->tableName)->where('product_id', $id)->update($validated);
        $productToCategory = DB::connection($this->database)->table($this->tableName)->where('product_id', $id)->first();

        return response()->json([
            'message' => 'Продукт-Категория #'.$id.' была обновлена',
            'data' => $productToCategory
        ], 201);
    }

    public function delete(int $id, Request $request) {
        DB::connection($this->database)->table($this->tableName)->where('product_id', $id)->delete();
        return response()->json([
            'message' => 'Продукт-Категория #'.$id." была удалена"
        ], 200);
    }
}
