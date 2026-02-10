<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group CosmoShop
 * @subgroup ProductProperty
 */
class ProductPropertyController extends Controller
{
    public $tableName = \TableName::ProductProperty->value;
    public $availableDatabases;
    public $database;
    public $pk = 'artikelid';
    public $sk = 'propertyid';
    public $flippedMapping;
    public $mapping = [
        'article_id' => 'artikelid',
        'property_id' => 'propertyid',
        'property_value' => 'propertyvalue',
    ];
    function changeFields($collection) {
        $mappedProduct = [];
        foreach ($this->flippedMapping as $dbField => $apiField) {
            if(isset($collection->$dbField)) $mappedProduct[$apiField] = $collection->$dbField;
        }
        return $mappedProduct;
    }

    public function __construct(Request $request){
        $array = env('DB_ARRAY');
        $availableDatabases =  json_decode($array);
        $this->database = $request->header('database');
        if (!$this->database || !in_array($this->database, $availableDatabases)) {
            abort(400, "Database header is required. Expected one of the database values: ".implode(', ', $availableDatabases));
        }
        $this->flippedMapping = array_flip($this->mapping);
    }

    public function create(Request $request) {
        $validated = $request->validate([
            'article_id' => 'required|integer|min:1'
        ]);
        $items = DB::connection($this->database)
            ->table($this->tableName)
            ->where('artikelid', $validated['article_id'])
            ->get();

        return response()->json([
            'message' => 'created',
            'data' => $items
        ], 201);
    }

    public function update(int $article_id, int $property_id, Request $request) {
        $validated = $request->validate([
            'property_value' => 'string',
        ]);
        $db = DB::connection($this->database);

        $mappedData = [];
        foreach ($validated as $key => $value) {
            if (isset($this->mapping[$key])) $mappedData[$this->mapping[$key]] = $value;
        }
        $oldItem = $db
            ->table($this->tableName)
            ->where($this->pk, $article_id)
            ->where($this->sk, $property_id)
            ->first(array_keys($mappedData));
        $affected = $db
            ->table($this->tableName)
            ->where($this->pk, $article_id)
            ->where($this->sk, $property_id)
            ->update($mappedData);
        if ($affected == 0) return response()->json(['message' => 'Item not found'], 404);
        $db->commit();
        $item = $db
            ->table($this->tableName)
            ->where($this->pk, $article_id)
            ->where($this->sk, $property_id)
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

    public function read(int $article_id, Request $request) {
        $items = DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->mapping['article_id'], $article_id)
            ->get();

        if (!$items) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $mappedItems = $items->map(function ($item) {
            return $this->changeFields($item);
        });

        return response()->json([
            'data' => $mappedItems
        ], 200);
    }

    public function delete(int $property_id, int $article_id, Request $request) {
        DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->mapping['property_id'], $property_id)
            ->where($this->mapping['article_id'], $article_id)
            ->delete();
        return response()->json([
            'message' => 'Item was deleted'
        ], 200);
    }
}
