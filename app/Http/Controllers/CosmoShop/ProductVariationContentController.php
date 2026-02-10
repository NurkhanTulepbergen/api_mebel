<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
/**
 * @group CosmoShop
 * @subgroup ProductVariationContent
 */
class ProductVariationContentController extends Controller
{
    public $tableName = 'shopartikelausfuehrungcontent';
    public $availableDatabases;
    public $database;
    public $pk = 'artikelausfuehrungid';
    public $sk = 'sprache';
    public $mapping = [
        'execution_id' => 'artikelausfuehrungid',
        'language' => 'sprache',
        'name' => 'bezeichnung',
        'name_wk' => 'bezeichnungwk',
        'description' => 'beschreibung',
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
            'execution_id' => 'required|integer|min:0',
            'language' => 'required|string',
            'name' => 'required|string|max:50',
            'name_wk' => 'nullable|string|max:255',
            'description' => 'nullable|string',
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
            ->where($this->pk, $validated['execution_id'])
            ->get();

        return response()->json([
            'message' => 'created',
            'data' => $item
        ], 201);
    }

    public function update(int $id, String $language, Request $request) {
        $validated = $request->validate([
            'language' => 'string|max:3',
            'name' => 'string|max:50',
            'name_wk' => 'nullable|string|max:255',
            'description' => 'nullable|string',
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

        $mappedItems = $items->map(function ($item) {
            return $this->changeFields($item);
        });

        return response()->json([
            'data' => $mappedItems
        ], 200);
    }

    public function delete(int $id, String $language, Request $request) {
        DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->where($this->mapping['language'], $language)
            ->delete();
        return response()->json([
            'message' => 'Item was deleted'
        ], 200);
    }
}
