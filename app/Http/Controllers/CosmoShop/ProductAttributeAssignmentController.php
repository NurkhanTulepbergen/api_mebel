<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group CosmoShop
 * @subgroup ProductAttributeAssignment
 */
class ProductAttributeAssignmentController extends Controller
{
    public $tableName = \TableName::ProductAttributeAssignment->value;
    public $availableDatabases;
    public $database;
    public $pk = 'article_id';
    public $sk = 'attribute_id';
    public $mapping = [
        'article_id' => 'element',
        'attribute_id' => 'gruppe',
        'sort_order' => 'sort'
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
            'article_id'    => 'required|integer|min:1',
            'attribute_id'  => 'required|integer|min:1',
            'sort_order'    => 'integer'
        ]);
        $mappedData = [];
        foreach ($validated as $key => $value) {
            if (isset($fieldMapping[$key])) {
                $mappedData[$fieldMapping[$key]] = $value;
            }
        }
        DB::connection($this->database)
            ->table($this->tableName)
            ->insert($mappedData);
        $item = DB::connection($this->database)
            ->table($this->tableName)
            ->where($fieldMapping['article_id'], $validated['article_id'])
            ->where($fieldMapping['attribute_id'], $validated['attribute_id'])
            ->get();

        return response()->json([
            'message' => 'created',
            'data' => $item
        ], 201);
    }

    public function update(int $article_id, int $attribute_id, Request $request) {
        $validated = $request->validate([
            'sort_order'    => 'required|integer'
        ]);
        $db = DB::connection($this->database);

        $mappedData = [];
        foreach ($validated as $key => $value) {
            if (isset($this->mapping[$key])) $mappedData[$this->mapping[$key]] = $value;
        }
        $oldItem = $db
            ->table($this->tableName)
            ->where($this->pk, $article_id)
            ->where($this->sk, $attribute_id)
            ->first(array_keys($mappedData));
        $affected = $db
            ->table($this->tableName)
            ->where($this->pk, $article_id)
            ->where($this->sk, $attribute_id)
            ->update($mappedData);
        if ($affected == 0) return response()->json(['message' => 'Item not found'], 404);
        $db->commit();
        $item = $db
            ->table($this->tableName)
            ->where($this->pk, $article_id)
            ->where($this->sk, $attribute_id)
            ->first(array_keys($mappedData));
        return response()->json([
            'message' => 'updated',
            'old_item' => $this->changeFields($oldItem),
            'new_item' => $this->changeFields($item),
        ], 200);
    }

    public function paginate(Request $request) {
        $fieldMapping = [
            'article_id' => 'element',
            'attribute_id' => 'gruppe',
            'sort_order' => 'sort'
        ];
        $fieldMapping = array_flip($fieldMapping);

        $items = DB::connection($this->database)
            ->table($this->tableName)
            ->paginate(100);

        $mappedProducts = $items->through(function ($price) use ($fieldMapping) {
            $mappedItem = [];
            foreach ($fieldMapping as $dbField => $apiField) {
                $mappedItem[$apiField] = $price->$dbField;
            }
            return $mappedItem;
        });

        return response()->json([
            'data' => $mappedProducts
        ], 200);
    }

    public function read(int $article_id, int $attribute_id, Request $request) {
        $fieldMapping = [
            'article_id' => 'element',
            'attribute_id' => 'gruppe',
            'sort_order' => 'sort'
        ];

        $dbFields = array_keys($fieldMapping);

        $item = DB::connection($this->database)
            ->table($this->tableName)
            ->where($fieldMapping['article_id'], $article_id)
            ->where($fieldMapping['attribute_id'], $attribute_id)
            ->first();

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }
        $fieldMapping = array_flip($fieldMapping);
        $mappedProduct = [];
        foreach ($fieldMapping as $dbField => $apiField) {
            $mappedProduct[$apiField] = $item->$dbField;
        }

        return response()->json([
            'data' => $mappedProduct
        ], 200);
    }

    public function delete(int $article_id, int $attribute_id, Request $request) {
        $fieldMapping = [
            'article_id' => 'element',
            'attribute_id' => 'gruppe',
            'sort_order' => 'sort'
        ];
        DB::connection($this->database)
        ->table($this->tableName)
        ->where($fieldMapping['article_id'], $article_id)
        ->where($fieldMapping['attribute_id'], $attribute_id)
        ->delete();
        return response()->json([
            'message' => 'Item was deleted'
        ], 200);
    }
}
