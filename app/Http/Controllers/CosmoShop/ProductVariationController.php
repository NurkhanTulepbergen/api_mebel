<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
/**
 * @group CosmoShop
 * @subgroup ProductVatiation
 */
class ProductVariationController extends Controller
{
    public $tableName = 'shopartikelausfuehrungen';
    public $availableDatabases;
    public $database;
    public $pk = 'artikelausfuehrungid';
    public $mapping = [
        'execution_id' => 'artikelausfuehrungid',
        'article_id' => 'artikelid',
        'execution_number' => 'ausfuehrungnr',
        'order' => 'order',
        'custom_key' => 'custom_key',
        'attribute_class_id' => 'attributsklasseid',
        'attribute_class_lock' => 'attributsklasse_lock'
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
            'article_id' => 'required|integer|min:1',
            'execution_number' => 'required|integer|min:0',
            'order' => 'integer|min:0',
            'custom_key' => 'nullable|string|max:50',
            'attribute_class_id' => 'integer',
            'attribute_class_lock' => 'boolean',
        ]);
        $mappedData = [];
        foreach ($validated as $key => $value) {
            if (isset($this->mapping[$key])) {
                $mappedData[$this->mapping[$key]] = $value;
            }
        }
        $id = DB::connection($this->database)
            ->table($this->tableName)
            ->insertGetId($mappedData);
        $item = DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->get();

        return response()->json([
            'message' => 'created',
            'data' => $item
        ], 201);
    }

    public function update(int $id, Request $request) {
        $validated = $request->validate([
            'article_id' => 'integer|min:1',
            'execution_number' => 'integer|min:0',
            'order' => 'integer|min:0',
            'custom_key' => 'nullable|string|max:50',
            'attribute_class_id' => 'integer',
            'attribute_class_lock' => 'boolean',
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
            'data' => $items
        ], 200);
    }

    public function read(int $id, Request $request) {
        $item = DB::connection($this->database)
            ->table($this->tableName)
            ->where('artikelid', $id)
            ->first();

        if (!$item) return response()->json(['message' => 'Item not found'], 404);

        $mappedProduct = [];
        foreach ($this->flippedMapping as $dbField => $apiField) {
            $mappedProduct[$apiField] = $item->$dbField;
        }

        return response()->json([
            'data' => $mappedProduct
        ], 200);
    }

    public function delete(int $id, Request $request) {
        DB::connection($this->database)->table($this->tableName)->where($this->pk, $id)->delete();
        return response()->json([
            'message' => 'Item was deleted'
        ], 200);
    }
}
