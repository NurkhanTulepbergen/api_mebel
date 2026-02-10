<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
/**
 * @group CosmoShop
 * @subgroup ProductStock
 */
class ProductStockController extends Controller
{
    public $tableName = 'shopartikelbestaende';
    public $pk = 'artikelid';
    public $mapping = [
        'article_id'       => 'artikelid',
        'stock'            => 'bestand',
        'min_stock'        => 'bestand_min',
        'ignore_stock'     => 'bestand_ignore',
        'created_at'       => 'timestamp',
        'storage_location' => 'storage',
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
            'article_id'        => 'required|integer',
            'stock'             => 'integer|min:0',
            'min_stock'         => 'integer|min:0',
            'ignore_stock'      => 'boolean',
            'created_at'        => 'date',
            'storage_location'  => 'string|max:250',
        ]);
        $validated['created_at'] = now();
        $db = DB::connection($this->database);
        $mappedData = [];
        foreach ($validated as $key => $value) {
            if (isset($this->mapping[$key])) {
                $mappedData[$this->mapping[$key]] = $value;
            }
        }
        $itemExists = $db
            ->table('shopartikel')
            ->where($this->pk, $validated['article_id'])
            ->exists();

        if(!$itemExists) return response()->json(['message' => 'Product does not exists'], 404);

        $db->beginTransaction();
        $db->table($this->tableName)
            ->insert($mappedData);
        $item = $db
            ->table($this->tableName)
            ->where($this->pk, $validated['article_id'])
            ->first();

        if (!$item) {
            $db->rollBack();
            return response()->json(['message' => 'Failed to create item'], 500);
        }
        $db->commit();
        return response()->json([
            'message' => 'Item was created',
            'data' => $this->changeFields($item)
        ], 201);
    }

    public function update(int $id, Request $request) {
        $validated = $request->validate([
            'stock'             => 'integer|min:0',
            'min_stock'         => 'integer|min:0',
            'ignore_stock'      => 'boolean',
            'created_at'        => 'date',
            'storage_location'  => 'string|max:250',
        ]);
        $db = DB::connection($this->database);

        $mappedData = [];
        foreach ($validated as $key => $value) {
            if (isset($this->mapping[$key])) $mappedData[$this->mapping[$key]] = $value;
        }
        $oldItem = $db
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->first(array_keys($mappedData));
        $affected = $db
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->update($mappedData);
        if ($affected == 0) return response()->json(['message' => 'Item not found'], 404);
        $db->commit();
        $item = $db
            ->table($this->tableName)
            ->where($this->pk, $id)
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

    public function read(int $id, Request $request) {
        $items = DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->pk, $id)
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

    public function delete(int $id, Request $request) {
        $deleted = DB::connection($this->database)->table($this->tableName)->where($this->pk, $id)->delete();
        if(!$deleted) abort(404, "Item was not found");
        return response()->json([
            'message' => $deleted.' items was deleted'
        ], 200);
    }
}
