<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
/**
 * @group CosmoShop
 * @subgroup ProductCategories
 */
class ProductCategoriesController extends Controller
{
    public $tableName = \TableName::ProductCategories->value;
    public $pk = 'artikelid';
    public $mapping = [
        'article_id'     => 'artikelid',
        'category_code'  => 'rubnum',
        'sort_order'     => 'ordnum',
        'priority_level' => 'priority',
        'category_id'    => 'rubid',
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
            'article_id'       => 'required|integer',
            'sort_order'       => 'integer',
            'priority_level'   => 'integer',
            'category_id'      => 'required|integer',
        ]);
        $db = DB::connection($this->database);
        $categoryCode = $db->table('shoprubriken')
            ->where('rubid', $validated['category_id'])
            ->pluck('rubnum')
            ->first();
        if(!$categoryCode){
            return response()->json(['message' => 'category not found'], 408);
        }
        $validated['category_code'] = $categoryCode;
        $mappedData = [];
        foreach ($validated as $key => $value) {
            if (isset($this->mapping[$key])) {
                $mappedData[$this->mapping[$key]] = $value;
            }
        }
        $db->beginTransaction();
        $db->table($this->tableName)
            ->insert($mappedData);
        $item = $db
            ->table($this->tableName)
            ->where($this->pk, $validated['article_id'])
            ->where('rubid', $validated['category_id'])
            ->first();

        if (!$item) {
            $db->rollBack();
            return response()->json(['message' => 'Failed to create item'], 500);
        }
        $db->commit();
        return response()->json([
            'message' => 'Item was created',
            'data' => $this->changeFields($item)
        ], 201);
    }

    public function update(int $id, Request $request) {
        $validated = $request->validate([
            'category_code'          => 'string',
            'sort_order'        => 'integer',
            'priority_level'    => 'integer',
            'category_id'       => 'integer',
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
        dd(1);
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

    public function delete(int $id, Request $request) {
        $deleted = DB::connection($this->database)->table($this->tableName)->where($this->pk, $id)->delete();
        if(!$deleted) abort(404, "Item was not found");
        return response()->json([
            'message' => $deleted.' items was deleted'
        ], 200);
    }
}
