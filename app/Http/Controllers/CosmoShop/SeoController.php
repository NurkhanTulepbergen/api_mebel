<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
/**
 * @group CosmoShop
 * @subgroup Seo
 */
class SeoController extends Controller
{
    public $tableName = \TableName::Seo->value;
    public $pk = 'id';
    public $mapping = [
        'type' => 'typ',
        'id' => 'id',
        'lang' => 'sprache',
        'page_title' => 'page_title',
        'meta_description' => 'meta_description',
        'meta_keywords' => 'meta_keywords',
    ];
    public $flippedMapping;
    public $selectArr = [];
    public $database;

    public function __construct(Request $request){
        $array = env('DB_ARRAY');
        $availableDatabases =  json_decode($array);
        $this->database = $request->header('database');
        if (!$this->database || !in_array($this->database, $availableDatabases)) {
            abort(400, "Database header is required. Expected one of the database values: ".implode(', ', $availableDatabases));
        }
        $this->flippedMapping = array_flip($this->mapping);
        foreach($this->mapping as $key => $value) {
            array_push($this->selectArr, "{$value} as {$key}");
        }
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
            'article_id' => 'nullable|integer',
            'category_id' => 'nullable|integer',
            'lang' => 'required|string',
            'page_title' => 'required|string',
            'meta_description' => 'required|string',
            'meta_keywords' => 'required|string',
        ], [
            'article_id.required_without:category_id' => 'Please provide either product_id or category_id.',
            'category_id.required_without:article_id' => 'Please provide either product_id or category_id.'
        ]);
        if ($request->filled('article_id') && $request->filled('category_id') || !$request->filled('article_id') && !$request->filled('category_id')) {
            return response()->json(['message' => 'Please provide either product_id or category_id, but not both'], 422);
        }
        $db = DB::connection($this->database);
        if($request->filled('article_id')) {
            $validated['type'] = 'a';
            $validated['id'] = $validated['article_id'];
            $isParentExists = $db->table(\TableName::Product->value)
                ->where('artikelid', $validated['id'])
                ->exists();

        } else if ($request->filled('category_id')) {
            $validated['type'] = 'r';
            $validated['id'] = $validated['category_id'];
            $isParentExists = $db->table(\TableName::Category->value)
                ->where('rubid', $validated['id'])
                ->exists();
        }
        if(!$isParentExists)
            return response()->json(['message' => 'Parent not found'], 404);

        $isSeoExists = $db->table($this->tableName)
                ->where('typ', $validated['type'])
                ->where('id', $validated['id'])
                ->exists();
        if($isSeoExists)
            return response()->json(['message' => 'Seo with that type and article already exists'], 404);


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
            ->where('id', $validated['id'])
            ->where('typ', $validated['type'])
            ->select($this->selectArr)
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
            'type' => 'in:a,z,r',
            'id' => 'integer',
            'lang' => 'string',
            'page_title' => 'string',
            'meta_description' => 'string',
            'meta_keywords' => 'string',
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

    public function delete(Request $request) {
        $validated = $request->validate([
            'lang' => 'required:string',
            'article_id' => 'nullable|integer',
            'category_id' => 'nullable|integer',
        ], [
            'article_id.required_without:category_id' => 'Please provide either product_id or category_id.',
            'category_id.required_without:article_id' => 'Please provide either product_id or category_id.'
        ]);
        if ($request->filled('article_id') && $request->filled('category_id')) {
            return response()->json(['message' => 'Please provide either product_id or category_id, but not both'], 422);
        }
        if($request->filled('article_id')) {
            $type = 'a';
            $id = $validated['article_id'];
        } else if ($request->filled('category_id')) {
            $type = 'r';
            $id = $validated['category_id'];
        }
        $deleted = DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->where('typ', $type)
            ->where('sprache', $validated['lang'])
            ->delete();
        if(!$deleted) abort(404, "Item was not found");
        return response()->json([
            'message' => $deleted.' items was deleted'
        ], 200);
    }
}
