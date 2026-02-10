<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
/**
 * @group CosmoShop
 * @subgroup ProductRecomendation
 */
class ProductRecomendationController extends Controller
{
    public $tableName = \TableName::ProductRecomendation->value;
    public $availableDatabases;
    public $database;
    public $pk = 'artikel';
    public $sk = 'empfehlung';
    public $mapping = [
        'primary_product_id' => 'artikel',
        'recommended_product_id' => 'empfehlung',
        'purchase_count' => 'anzahl'
    ];


    public $flippedMapping;

    public function __construct(Request $request){
        $array = env('DB_ARRAY');
        $availableDatabases =  json_decode($array);
        $this->database = $request->header('database');
        if (!$this->database || !in_array($this->database, $availableDatabases)) {
            abort(400, "Database header is required. Expected one of the database values: ".implode(', ', $availableDatabases));
        }
        $this->flippedMapping = array_flip($this->mapping);
    }
    function changeFields($collection) {
        $mappedProduct = [];
        foreach ($this->flippedMapping as $dbField => $apiField) {
            if(isset($collection->$dbField)) $mappedProduct[$apiField] = $collection->$dbField;
        }
        return $mappedProduct;
    }

    public function create(Request $request) {
        $validated = $request->validate([
            'primary_product_id' => 'required|string|max:100',
            'recommended_product_id' => 'required|string|max:220',
            'purchase_count' => 'required|integer|min:0'
        ]);
        $mappedData = [];
        foreach ($validated as $key => $value) {
            if (isset($this->mapping[$key])) {
                $mappedData[$this->mapping[$key]] = $value;
            }
        }
        DB::connection($this->database)
            ->table($this->tableName)
            ->insert($mappedData);
        $item = DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->pk, $validated['primary_product_id'])
            ->where($this->sk, $validated['recommended_product_id'])
            ->first();

        return response()->json([
            'message' => 'created',
            'data' => $item
        ], 201);
    }

    public function update(String $primary_product_id, String $recomended_product_id, Request $request) {
        $validated = $request->validate([
            'purchase_count' => 'integer|min:0'
        ]);
        $db = DB::connection($this->database);

        $mappedData = [];
        foreach ($validated as $key => $value) {
            if (isset($this->mapping[$key])) $mappedData[$this->mapping[$key]] = $value;
        }
        $oldItem = $db
            ->table($this->tableName)
            ->where($this->pk, $primary_product_id)
            ->where($this->sk, $recomended_product_id)
            ->first(array_keys($mappedData));
        $affected = $db
            ->table($this->tableName)
            ->where($this->pk, $primary_product_id)
            ->where($this->sk, $recomended_product_id)
            ->update($mappedData);
        if ($affected == 0) return response()->json(['message' => 'Item not found'], 404);
        $db->commit();
        $item = $db
            ->table($this->tableName)
            ->where($this->pk, $primary_product_id)
            ->where($this->sk, $recomended_product_id)
            ->first(array_keys($mappedData));
        return response()->json([
            'message' => 'updated',
            'old_item' => $this->changeFields($oldItem),
            'new_item' => $this->changeFields($item),
        ], 200);
    }

    public function paginate(Request $request) {
        $items = DB::connection($this->database)
            ->table($this->tableName)
            ->paginate(100);

        $mappedItems = $items->map(function ($item) {
            return $this->changeFields($item);
        });

        return response()->json([
            'data' => $mappedItems
        ], 200);
    }

    public function read(String $primary_product_id, Request $request) {
        $item = DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->pk, $primary_product_id)
            ->get();

        if (!$item) return response()->json(['message' => 'Item not found'], 404);

        $mappedItems = $items->map(function ($item) {
            return $this->changeFields($item);
        });
        return response()->json([
            'data' => $mappedItems
        ], 200);
    }

    public function delete(String $primary_product_id, String $recomended_product_id, Request $request) {
        DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->pk, $primary_product_id)
            ->where($this->sk, $recomended_product_id)
            ->delete();
        return response()->json([
            'message' => 'Item was deleted'
        ], 200);
    }
}
