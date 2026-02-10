<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
/**
 * @group CosmoShop
 * @subgroup ProductVariantContent
 */
class ProductVariantContentController extends Controller
{
    public $tableName = 'shopartikelvariantecontent';
    public $availableDatabases;
    public $database;
    public $pk = 'artikelvarianteid';
    public $sk = 'sprache';
    public $mapping = [
        'variant_id' => 'artikelvarianteid',
        'language' => 'sprache',
        'designation' => 'bezeichnung',
        'description' => 'beschreibung'
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
            'variant_id' => 'required|integer',
            'language' => 'required|string',
            'designation' => 'required|string|max:50',
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
            ->where($this->pk, $validated['variant_id'])
            ->get();

        return response()->json([
            'message' => 'created',
            'data' => $this->changeFields($item)
        ], 201);
    }

    public function update(int $id, String $language, Request $request) {
        $validated = $request->validate([
            'language' => 'string',
            'designation' => 'string|max:50',
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

    public function delete(int $id, String $language, Request $request) {
        $deleted = DB::connection($this->database)
        ->table($this->tableName)
        ->where($this->pk, $id)
        ->where($this->sk, $language)
        ->delete();
        if(!$deleted) abort(404, "Item was not found");
        return response()->json([
            'message' => $deleted.' items was deleted'
        ], 200);
    }
}
