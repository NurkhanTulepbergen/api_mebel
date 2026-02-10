<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
/**
 * @group CosmoShop
 * @subgroup StockMovement
 */
class StockMovementController extends Controller
{
    public $tableName = 'shopartikelbestand_buchung';
    public $availableDatabases;
    public $database;
    public $pk = 'buchung_id';
    public $mapping = [
        'booking_id' => 'buchung_id',
        'article_id' => 'artikelid',
        'booking_time' => 'buchungszeit',
        'booking_type' => 'typ',
        'booking_quantity' => 'buchung',
        'origin' => 'herkunft',
        'order_number' => 'best_nr',
        'current_stock' => 'stand_aktuell',
        'previous_stock' => 'stand_zuvor',
        'details' => 'detail',
        'stack_trace' => 'stacktrace',
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
        $db = DB::connection($this->database);
        $validated = $request->validate([
            'article_id' => 'required|integer|min:1',
            'booking_time' => 'date',
            'booking_type' => 'required|in:buchung,reservierung,absolut,delete',
            'booking_quantity' => 'required|integer',
            'origin' => 'required|string|max:120',
            'order_number' => 'string|max:20',
            'current_stock' => 'required|string|max:250',
            'previous_stock' => 'required|string|max:250',
            'details' => 'string',
            'stack_trace' => 'string',
            'storage_location' => 'string|max:250',
        ]);
        $mappedData = [];
        foreach ($validated as $key => $value) {
            if (isset($this->mapping[$key])) {
                $mappedData[$this->mapping[$key]] = $value;
            }
        }
        $db->beginTransaction();
        $id = $db
            ->table($this->tableName)
            ->insertGetId($mappedData);
        $product = $db
            ->table($this->tableName)
            ->where('buchung_id', $id)
            ->first();
        if (!$product) {
            $db->rollBack();
            return response()->json(['message' => 'Failed to create item'], 500);
        }
        $db->commit();
        return response()->json([
            'message' => 'Item was created',
            'data' => $this->changeFields($product)
        ], 201);
    }

    public function update(int $id, Request $request) {
        $validated = $request->validate([
            'article_id' => 'integer|min:1',
            'booking_time' => 'date',
            'booking_type' => 'in:buchung,reservierung,absolut,delete',
            'booking_quantity' => 'integer',
            'origin' => 'string|max:120',
            'order_number' => 'string|max:20',
            'current_stock' => 'string|max:250',
            'previous_stock' => 'string|max:250',
            'details' => 'string',
            'stack_trace' => 'string',
            'storage_location' => 'string|max:250',
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
            'data' => $this->changeFields($mappedItems)
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
