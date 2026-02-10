<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
/**
 * @group CosmoShop
 * @subgroup ProductContent
 */
class ProductContentController extends Controller
{
    public $tableName = \TableName::ProductContent->value;
    public $pk = 'artikelid';
    public $mapping = [
        'article_id'           => 'artikelid',
        'language'             => 'sprache',
        'title'                => 'name',
        'description'          => 'bezeichnung',
        'description_html'     => 'bezeichnung_html',
        'short_description'    => 'bezeichnung_kurz',
        'is_plain_description' => 'bezeichnung_plain',
        'keywords'             => 'keywords',
        'image_alt_text'       => 'bilder_alt',
        'search_field'         => 'suchfeld',
        'live_shopping_text'   => 'liveshopping_text',
        'url_key'              => 'urlkey',
        'second_price_label'   => 'second_price_label',
        'features'             => 'features',
    ];
    public $flippedMapping;
    private $database;

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

    public function getUrl(Request $request) {
        $validated = $request->validate([
            'language' => 'required|string',
            'ids' => 'required|array'
        ]);
        if(str_contains($this->database, 'jv')) {
            $items = DB::connection($this->database)->table($this->tableName)->whereIn($this->pk, $validated['ids'])->pluck('urlkey', 'artikelid')->toArray();
        } else {
            $items = DB::connection($this->database)->table('oc_products')->whereIn($validated['key'], $validated['value'])->pluck('product_id', $validated['key'])->toArray();
        }
        return response()->json([
            'data' => $items
        ], 200);
    }

    public function create(Request $request) {
        $validated = $request->validate([
            'article_id'           => 'required|integer',
            'language'             => 'string|max:3',
            'title'                => 'string|max:250',
            'description'          => 'nullable|string',
            'description_html'     => 'nullable|string',
            'short_description'    => 'nullable|string',
            'is_plain_description' => 'boolean',
            'keywords'             => 'nullable|string',
            'image_alt_text'       => 'nullable|string|max:250',
            'search_field'         => 'nullable|string',
            'live_shopping_text'   => 'nullable|string',
            'url_key'              => 'string|max:255',
            'second_price_label'   => 'nullable|string',
            'features'             => 'nullable|string',
        ]);
        $db = DB::connection($this->database);
        $db->beginTransaction();

        $mappedData = [];
        foreach ($validated as $key => $value) {
            if (isset($this->mapping[$key])) {
                $mappedData[$this->mapping[$key]] = $value;
            }
        }

        $isAUrlKeyExists = DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->mapping['url_key'], $validated['url_key'])
            ->exists();
        if($isAUrlKeyExists) {
            $db->rollBack();
            return response()->json(['message' => 'Item with that URL key already exists. You need to create a unique URL key'], 404);
        }

        $db->table($this->tableName)
            ->insert($mappedData);
        $item = $db
            ->table($this->tableName)
            ->where($this->pk, $validated['article_id'])
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
            'language'             => 'string|max:3',
            'title'                => 'string|max:250',
            'description'          => 'nullable|string',
            'description_html'     => 'nullable|string',
            'short_description'    => 'nullable|string',
            'is_plain_description' => 'boolean',
            'keywords'             => 'nullable|string',
            'image_alt_text'       => 'nullable|string|max:250',
            'search_field'         => 'nullable|string',
            'live_shopping_text'   => 'nullable|string',
            'url_key'              => 'string|max:255',
            'second_price_label'   => 'nullable|string',
            'features'             => 'nullable|string',
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
            'data' => $mappedItems,
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
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
