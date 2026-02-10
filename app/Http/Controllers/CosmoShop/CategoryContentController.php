<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group CosmoShop
 * @subgroup CategoryContent
 */
class CategoryContentController extends Controller
{
    public $tableName = \TableName::CategoryContent->value;
    private $database;
    public $pk = 'rubid';
    public $mapping = [
        'category_code_ref' => 'rubnumref',
        'category_id' => 'rubid',
        'language' => 'rubsprache',
        'category_name' => 'rubnam',
        'description' => 'rubtext',
        'meta_keywords' => 'keywords',
        'short_description' => 'rubtext_kurz',
        'url_key' => 'urlkey',
        'slider_id' => 'slider_id',
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
            'category_code_ref' => 'required|string|max:200|exists:shoprubriken,rubnum',
            'language' => 'required|string|size:3',
            'category_name' => 'required|string|max:250',
            'description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'short_description' => 'nullable|string',
            'url_key' => 'required|string|max:255',
            'slider_id' => 'nullable|string|max:255',
            'category_id' => 'required|integer|exists:shoprubriken,rubid'
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
            ->where($this->pk, $id)
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
        $db = DB::connection($this->database);
        $validated = $request->validate([
            'category_code_ref' => 'required|string|max:200|exists:shoprubriken,rubnum',
            'language' => 'required|string|size:3',
            'category_name' => 'required|string|max:250',
            'description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'short_description' => 'nullable|string',
            'url_key' => 'required|string|max:255',
            'slider_id' => 'nullable|string|max:255',
            'category_id' => 'required|integer|exists:shoprubriken,rubid'
        ]);

        $mappedData = [];
        foreach ($validated as $key => $value) {
            if (isset($this->mapping[$key])) {
                $mappedData[$this->mapping[$key]] = $value;
            }
        }
        $oldItem = $db
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->first();
        $affected = $db
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->update($mappedData);
        dd($affected);
        if ($affected > 1) {
            $db->rollBack();
            return response()->json(['message' => 'Item not found'], 404);
        }
        $db->commit();
        $item = $db
        ->table($this->tableName)
        ->where($this->pk, $id)
        ->select(array_keys($mappedData))
        ->first();
        return response()->json([
            'message' => 'updated',
            'old_item' => $this->changeFields($oldItem),
            'new_item' => $this->changeFields($item),
        ], 201);
    }

    public function paginate(Request $request) {
        $products = DB::connection($this->database)
            ->table($this->tableName)
            ->paginate(100);

        $mappedItems = $products->map(function ($item) {
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
            ->get();
        // dd($item);

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }
        $mappedItems = $item->map(function ($item) {
            return $this->changeFields($item);
        });
        return response()->json([
            'data' => $mappedItems
        ], 200);
    }

    public function delete(int $id, Request $request) {
        $db = DB::connection($this->database);
        $db->beginTransaction();
        $deleted = $db->table($this->tableName)->where($this->pk, $id)->delete();
        if(!$deleted) {
            $db->rollBack();
            abort(404, "Item was not found");
        }
        $db->commit();
        return response()->json([
            'message' => $deleted.' item(s) was deleted',
        ], 200);
    }
}
