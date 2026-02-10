<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group CosmoShop
 * @subgroup AttributeContent
 */
class AttributeContentController extends Controller
{
    public $tableName = \TableName::AttributeContent->value;
    public $availableDatabases;
    public $database;
    public $pk = 'id';
    public $sk = 'sprache';
    public $mapping = [
        'attribute_id' => 'id',
        'language' => 'sprache',
        'attribute_name' => 'name',
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
            'attribute_id' => 'required|integer|min:1',
            'language' => 'required|string',
            'attribute_name' => 'required|string',
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
            ->where($this->pk, $validated['attribute_id'])
            ->get();

        return response()->json([
            'message' => 'created',
            'data' => $item
        ], 201);
    }

    public function update(int $id, String $language, Request $request) {
        $validated = $request->validate([
            'language' => 'required|string',
            'attribute_name' => 'required|string',
        ]);
        $db = DB::connection($this->database);

        $mappedData = [];
        foreach ($validated as $key => $value) {
            if (isset($this->mapping[$key])) $mappedData[$this->mapping[$key]] = $value;
        }
        $oldItem = $db
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->where($this->sk, $language)
            ->first(array_keys($mappedData));
        $affected = $db
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->where($this->sk, $language)
            ->update($mappedData);
        if ($affected == 0) return response()->json(['message' => 'Item not found'], 404);
        $db->commit();
        $item = $db
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->where($this->sk, $language)
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

        $mappedItems = $items->through(function ($item) {
            $mappedItem = [];
            foreach ($this->flippedMapping as $dbField => $apiField) {
                $mappedItem[$apiField] = $item->$dbField;
            }
            return $mappedItem;
        });

        return response()->json([
            'data' => $items
        ], 200);
    }

    public function read(int $id, Request $request) {
        $item = DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->first();

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $mappedProduct = [];
        foreach ($this->flippedMapping as $dbField => $apiField) {
            $mappedProduct[$apiField] = $item->$dbField;
        }

        return response()->json([
            'data' => $mappedProduct
        ], 200);
    }

    public function delete(int $id, String $language, Request $request) {
        DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->where($this->sk, $language)
            ->delete();
        return response()->json([
            'message' => 'Item was deleted'
        ], 200);
    }
}
