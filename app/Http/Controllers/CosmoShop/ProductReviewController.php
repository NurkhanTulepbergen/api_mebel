<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
/**
 * @group CosmoShop
 * @subgroup ProductReview
 */
class ProductReviewController extends Controller
{
    public $tableName = 'shopartikelbewertung';
    public $availableDatabases;
    public $database;
    public $pk = 'id';
    public $mapping = [
        'review_id' => 'id',
        'article_number' => 'artnum',
        'customer_id' => 'kd_id',
        'last_updated' => 'letzter',
        'date' => 'datum',
        'is_read' => 'gelesen',
        'helpful_count' => 'hilfreich',
        'title' => 'titel',
        'review_text' => 'bewertung',
        'rating' => 'sterne'
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
            'article_number' => 'required|string|max:100',
            'customer_id' => 'required|integer|min:1',
            'last_updated' => 'date',
            'date' => 'date',
            'is_read' => 'boolean',
            'helpful_count' => 'integer|min:0',
            'title' => 'required|string|max:255',
            'review_text' => 'required|string',
            'rating' => 'required|integer|min:0|max:5',
        ]);
        $validated['last_updated']  = now();
        $validated['date']          = now();
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
            'customer_id' => 'integer|min:1',
            'last_updated' => 'date',
            'date' => 'date',
            'is_read' => 'boolean',
            'helpful_count' => 'integer|min:0',
            'title' => 'string|max:255',
            'review_text' => 'string',
            'rating' => 'integer|min:0|max:5',
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
            ->where($this->pk, $id)
            ->first();

        if (!$item) return response()->json(['message' => 'Item not found'], 404);

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
