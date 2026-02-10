<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
/**
 * @group CosmoShop
 * @subgroup ProductSupplierInfo
 */
class ProductSupplierInfoController extends Controller
{
    public $tableName = 'shopartikellieferanteninfo';
    public $availableDatabases;
    public $database;
    public $pk = 'lieferanteninfoid';
    public $mapping = [
        'supplier_info_id' => 'lieferanteninfoid',
        'article_id' => 'artikelid',
        'supplier_id' => 'lieferantid',
        'supplier_article_number' => 'liefernr',
        'purchase_price' => 'preis_ek',
        'sold_quantity' => 'abverkauf',
        'delivery_time' => 'lieferzeit',
        'delivery_date' => 'lieferdatum'
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
            'article_id'                => 'required|integer|min:1',
            'supplier_id'               => 'sometimes|integer|min:0',
            'supplier_article_number'   => 'string|max:200',
            'purchase_price'            => 'required|numeric|min:0',
            'sold_quantity'             => 'sometimes|integer',
            'delivery_time'             => 'sometimes|integer|min:0',
            'delivery_date'             => 'sometimes|date'
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
            'article_id'                => 'sometimes|integer|min:1',
            'supplier_id'               => 'sometimes|integer|min:0',
            'supplier_article_number'   => 'sometimes|string|max:200',
            'purchase_price'            => 'sometimes|numeric|min:0',
            'sold_quantity'             => 'sometimes|integer',
            'delivery_time'             => 'sometimes|integer|min:0',
            'delivery_date'             => 'sometimes|date'
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
        $item = DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->first();

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        return response()->json([
            'data' => $this->changeFields($item)
        ], 200);
    }

    public function delete(int $id, Request $request) {
        DB::connection($this->database)->table($this->tableName)->where($this->pk, $id)->delete();
        return response()->json([
            'message' => 'Item was deleted'
        ], 200);
    }
}
