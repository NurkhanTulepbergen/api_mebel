<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class ProductAttributeController extends Controller
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
            'attribute_id'  => 'required|integer',
            'language_id'   => 'required|integer',
            'text'          => 'required|string'
        ]);
        DB::connection($this->database)->table($this->tableName)->insertGetId($validated);
        $attribute = DB::connection($this->database)->table($this->tableName)->where('product_id', $validated['product_id'])->first();

        return response()->json([
            'message' => 'Атрибут был создан',
            'data' => $attribute
        ], 201);
    }

    public function read(int $id, Request $request) {
        $product = DB::connection($this->database)
            ->table($this->tableName)
            ->first();
        return response()->json([
            'message' => 'Атрибут #'.$id,
            'data' => $product
        ], 200);
    }

    public function paginate(Request $request) {
        $products = DB::connection($this->database)->table($this->tableName)->paginate(10);
        return response()->json([
            'data' => $products
        ], 200);
    }

    public function update(Request $request, int $id) {
        $validated = $request->validate([
            'attribute_id'  => 'integer',
            'language_id'   => 'integer',
            'text'          => 'string'
        ]);

        $product = DB::connection($this->database)->table($this->tableName)->where('product_id', $id)->update($validated);
        $product = DB::connection($this->database)->table($this->tableName)->where('product_id', $id)->first();

        return response()->json([
            'message' => 'Атрибут продукта #'.$id.' обновлен',
            'data' => $product
        ], 201);
    }

    public function delete(int $id, Request $request) {
        DB::connection($this->database)->table($this->tableName)->where('product_id', $id)->delete();
        return response()->json([
            'message' => 'Атрибут #'.$id." был удален"
        ], 200);
    }
}
