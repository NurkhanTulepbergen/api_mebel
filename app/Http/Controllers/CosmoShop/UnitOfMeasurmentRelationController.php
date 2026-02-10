<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
/**
 * @group CosmoShop
 * @subgroup UnitOfMeasurmentRelation
 */
class UnitOfMeasurmentRelationController extends Controller
{
    public $tableName = 'shopeinheiten_rel';
    public $availableDatabases;
    public $database;
    public $pk = 'ober_einheit';
    public $sk = 'unter_einheit';
    public $mapping = [
        'base_unit_id' => 'ober_einheit',
        'sub_unit_id' => 'unter_einheit',
        'conversion_factor' => 'divisor'
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
            'base_unit_id' => 'required|integer|min:1',
            'sub_unit_id' => 'required|string|max:250',
            'conversion_factor' => 'required|integer|min:1'
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
            ->where($this->pk, $validated['base_unit_id'])
            ->where($this->sk, $validated['sub_unit_id'])
            ->get();

        return response()->json([
            'message' => 'created',
            'data' => $item
        ], 201);
    }

    public function update(int $base_unit_id, String $sub_unit_id, Request $request) {
        $validated = $request->validate([
            'base_unit_id' => 'integer|min:1',
            'sub_unit_id' => 'string|max:250',
            'conversion_factor' => 'integer|min:1'
        ]);
        $db = DB::connection($this->database);

        $mappedData = [];
        foreach ($validated as $key => $value) {
            if (isset($this->mapping[$key])) $mappedData[$this->mapping[$key]] = $value;
        }
        $oldItem = $db
            ->table($this->tableName)
            ->where($this->pk, $base_unit_id)
            ->where($this->sk, $sub_unit_id)
            ->first(array_keys($mappedData));
        $affected = $db
            ->table($this->tableName)
            ->where($this->pk, $base_unit_id)
            ->where($this->sk, $sub_unit_id)
            ->update($mappedData);
        if ($affected == 0) return response()->json(['message' => 'Item not found'], 404);
        $db->commit();
        $item = $db
            ->table($this->tableName)
            ->where($this->pk, $base_unit_id)
            ->where($this->sk, $sub_unit_id)
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

    public function delete(int $base_unit_id, String $sub_unit_id, Request $request) {
        DB::connection($this->database)
        ->table($this->tableName)
        ->where($this->pk, $base_unit_id)
        ->where($this->sk, $sub_unit_id)
        ->delete();
        return response()->json([
            'message' => 'Item was deleted'
        ], 200);
    }
}
