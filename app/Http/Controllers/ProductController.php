<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class ProductController extends Controller
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
            'model' => 'required|string',
            'sku' => 'string',
            'upc' => 'string',
            'ean' => 'string',
            'jan' => 'string',
            'isbn' => 'string',
            'mpn' => 'string',
            'location' => 'string',
            'quantity' => 'required|integer',
            'stock_status_id' => 'required|integer',
            'image' => 'string',
            'manufacturer_id' => 'integer',
            'shipping' => 'required|boolean',
            'price' => 'required|integer',
            'tax_class_id' => 'integer',
            'date_available' => 'date',
            'weight' => 'integer',
            'weight_class_id' => 'integer',
            'length' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'length_class_id' => 'integer',
            'subtract' => 'integer',
            'minimum' => 'integer',
            'sort_order' => 'integer',
            'status' => 'integer',
            'viewed' => 'integer',
            'date_added' => 'date',
            'date_modified' => 'date',
        ]);
        $validated['sku'] = $validated['sku'] ?? '';
        $validated['upc'] = $validated['upc'] ?? '';
        $validated['ean'] = $validated['ean'] ?? '';
        $validated['jan'] = $validated['jan'] ?? '';
        $validated['isbn'] = $validated['isbn'] ?? '';
        $validated['mpn'] = $validated['mpn'] ?? '';
        $validated['location'] = $validated['location'] ?? '';
        $validated['image'] = $validated['image'] ?? '';
        $validated['manufacturer_id'] = $validated['manufacturer_id'] ?? 0;
        $validated['tax_class_id'] = $validated['tax_class_id'] ?? 0;
        $validated['date_added'] = $validated['date_added'] ?? now();
        $validated['date_modified'] = $validated['date_modified'] ?? now();

        $productId = DB::connection($this->database)->table($this->tableName)->insertGetId($validated);
        $product = DB::connection($this->database)->table($this->tableName)->where('product_id', $productId)->first();

        return response()->json([
            'message' => 'Продукт был создан',
            'data' => $product
        ], 201);
    }

    public function getId(Request $request) {
        $validated = $request->validate([
            'key' => 'required|string',
            'value' => 'required|array'
        ]);
        $products = DB::connection($this->database)->table($this->tableName)->whereIn($validated['key'], $validated['value'])->pluck('product_id', $validated['key'])->toArray();
        if(!$products) {
            return response()->json([
                'message' => 'Продукт с '.$validated['key'].' - '.$validated['value'].'не найден'
            ], 404);
        }
        return response()->json([
            'product_id' => $products
        ], 200);
    }

    public function read(int $id, Request $request) {
        $product = DB::connection($this->database)->table($this->tableName)->where('product_id', $id)->first();
        return response()->json([
            'message' => 'Продукт #'.$id,
            'data' => $product
        ], 200);
    }

    public function paginate(Request $request) {
        $products = DB::connection($this->database)->table($this->tableName)->paginate(100);
        return response()->json([
            'data' => $products
        ], 200);
    }

    public function update(Request $request, int $id) {
        $validated = $request->validate([
            'model' => 'string',
            'sku' => 'string',
            'upc' => 'string',
            'ean' => 'string',
            'jan' => 'string',
            'isbn' => 'string',
            'mpn' => 'string',
            'location' => 'string',
            'quantity' => 'integer',
            'stock_status_id' => 'integer',
            'image' => 'string',
            'manufacturer_id' => 'integer',
            'shipping' => 'boolean',
            'price' => 'integer',
            'tax_class_id' => 'integer',
            'date_available' => 'date',
            'weight' => 'integer',
            'weight_class_id' => 'integer',
            'length' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'length_class_id' => 'integer',
            'subtract' => 'integer',
            'minimum' => 'integer',
            'sort_order' => 'integer',
            'status' => 'integer',
            'viewed' => 'integer',
            'date_added' => 'date',
            'date_modified' => 'date',
        ]);

        $product = DB::connection($this->database)->table($this->tableName)->where('product_id', $id)->update($validated);
        $product = DB::connection($this->database)->table($this->tableName)->where('product_id', $id)->first();

        return response()->json([
            'message' => 'Продукт #'.$id.' обновлен',
            'data' => $product
        ], 201);
    }

    public function delete(int $id, Request $request) {
        DB::connection($this->database)->table($this->tableName)->where('product_id', $id)->delete();
        return response()->json([
            'message' => 'Продукт #'.$id." был удален"
        ], 200);
    }
}
