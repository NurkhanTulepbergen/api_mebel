<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
/**
 * @group CosmoShop
 * @subgroup Property
 */
class PropertyController extends Controller
{
    public $tableName = 'shopproperties';
    public $availableDatabases;
    public $database;
    public $pk = 'id';
    public $mapping = [
        'property_id' => 'id',
        'property_name' => 'propertyname',
        'property_code' => 'propertycode',
        'is_visible_frontend' => 'frontendavailable',
        'is_visible_backend' => 'backendavailable',
        'plugin_name' => 'plugin',
        'sort_order' => 'sort',
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
            'property_name' => 'required|string|max:255',
            'property_code' => 'required|string|max:50',
            'is_visible_frontend' => 'boolean',
            'is_visible_backend' => 'boolean',
            'plugin_name' => 'string|max:50',
            'sort_order' => 'integer|min:0',
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
            'property_name' => 'string|max:255',
            'property_code' => 'string|max:50',
            'is_visible_frontend' => 'boolean',
            'is_visible_backend' => 'boolean',
            'plugin_name' => 'string|max:50',
            'sort_order' => 'integer|min:0',
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

    public function delete(int $id, Request $request) {
        DB::connection($this->database)->table($this->tableName)->where($this->pk, $id)->delete();
        return response()->json([
            'message' => 'Item was deleted'
        ], 200);
    }
}
