<?php

namespace App\Http\Controllers\OpenCart;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group OpenCart
 * @subgroup UrlAlias
 */
class UrlAliasController extends Controller
{
    private $database;
    private $tableName = 'oc_url_alias';

    public function __construct(Request $request){
        $array = env('DB_ARRAY');
        $availableDatabases =  json_decode($array);
        $this->database = $request->header('database');
        if (!$this->database || !in_array($this->database, $availableDatabases)) {
            abort(400, "Database header is required. Expected one of the database values: ".implode(', ', $availableDatabases));
        }
    }

    /**
     * Create URL alias.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: xl.de
     * @bodyParam id integer required Entity identifier to alias. Example: 55
     * @bodyParam content_type string required Must be either `category` or `product`. Example: category
     * @bodyParam url string required SEO-friendly slug. Example: living-room-furniture
     * @response 201 {
     *   "message": "Item was created",
     *   "data": {
     *     "url_alias_id": 1234,
     *     "query": "category_id=55",
     *     "keyword": "living-room-furniture"
     *   }
     * }
     * @response 400 scenario="Duplicate slug" {
     *   "message": "Url with that slug already exists"
     * }
     * @response 500 {
     *   "message": "Failed to create item"
     * }
     */
    public function create(Request $request) {
        $validated = $request->validate([
            'id' => 'required|integer',
            'content_type' => 'required|in:category,product',
            'url' => 'required|string'
        ]);
        $fields = [];
        if(in_array($validated['content_type'], ['category', 'product']))
            $fields['query'] = "{$validated['content_type']}_id={$validated['id']}";
        $fields['keyword'] = $validated['url'];
        $db = DB::connection($this->database);
        $db->beginTransaction();
        $item = $db->table($this->tableName)
            ->where('keyword', $validated['url'])
            ->exists();
        if($item) return response()->json(['message' => 'Url with that slug already exists'], 400);
        $db->table($this->tableName)
            ->insert($fields);
        $item = $db
            ->table($this->tableName)
            ->where('query', $fields['query'])
            ->first();

        if (!$item) {
            $db->rollBack();
            return response()->json(['message' => 'Failed to create item'], 500);
        }
        $db->commit();
        return response()->json([
            'message' => 'Item was created',
            'data' => $item
        ], 201);
    }

    /**
     * Update URL alias.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: xl.de
     * @urlParam id integer required Identifier of the entity whose alias should be updated. Example: 55
     * @bodyParam content_type string required Must be either `category` or `product`. Example: category
     * @bodyParam url string required New SEO-friendly slug. Example: modern-living-room
     * @response 200 {
     *   "message": "Item was updated",
     *   "new": {
     *     "query": "category_id=55",
     *     "keyword": "modern-living-room"
     *   },
     *   "old": {
     *     "query": "category_id=55",
     *     "keyword": "living-room-furniture"
     *   }
     * }
     * @response 400 scenario="Duplicate slug" {
     *   "message": "Url with that slug already exists"
     * }
     * @response 500 {
     *   "message": "Failed to create item"
     * }
     */
    public function update(int $id, Request $request) {
        $validated = $request->validate([
            'content_type' => 'required|in:category,product',
            'url' => 'required|string'
        ]);
        $fields = [];
        if(in_array($validated['content_type'], ['category', 'product']))
            $fields['query'] = "{$validated['content_type']}_id={$validated['id']}";
        $fields['keyword'] = $validated['url'];
        $db = DB::connection($this->database);
        $db->beginTransaction();
        $oldItem = $db
            ->table($this->tableName)
            ->where('query', $fields['query'])
            ->first();
        $isExists = $db->table($this->tableName)
            ->where('keyword', $validated['url'])
            ->exists();
        if($isExists) return response()->json(['message' => 'Url with that slug already exists'], 400);
        $db->table($this->tableName)
            ->where('query', $fields['query'])
            ->update($fields);
        $item = $db
            ->table($this->tableName)
            ->where('query', "category_id={$id}")
            ->first();

        if (!$item) {
            $db->rollBack();
            return response()->json(['message' => 'Failed to create item'], 500);
        }
        $db->commit();
        return response()->json([
            'message' => 'Item was updated',
            'new' => $item,
            'old' => $oldItem,
        ], 200);
    }

    /**
     * Paginate URL aliases.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: xl.de
     * @queryParam page integer Page number to return. Example: 2
     * @response 200 {
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "url_alias_id": 1234,
     *         "query": "category_id=55",
     *         "keyword": "living-room-furniture"
     *       }
     *     ],
     *     "per_page": 100,
     *     "total": 1
     *   },
     *   "pagination": {
     *     "current_page": 1,
     *     "last_page": 1,
     *     "per_page": 100,
     *     "total": 1
     *   }
     * }
     */
    public function paginate(Request $request) {
        $items = DB::connection($this->database)
            ->table($this->tableName)
            ->paginate(100);

        return response()->json([
            'data' => $items,
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ], 200);
    }

    /**
     * Read URL alias.
     *
     * Currently only category aliases are supported.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: xl.de
     * @urlParam id integer required Identifier of the entity to fetch. Example: 55
     * @bodyParam content_type string required Must be `category`. Example: category
     * @response 200 {
     *   "data": {
     *     "url_alias_id": 1234,
     *     "query": "category_id=55",
     *     "keyword": "living-room-furniture"
     *   }
     * }
     * @response 404 {
     *   "message": "Item not found"
     * }
     */
    public function read(int $id, Request $request) {
        $validated = $request->validate([
            'content_type' => 'required|in:category',
        ]);
        if($validated['content_type'] == 'category')
            $query = "category_id={$id}";
        $items = DB::connection($this->database)
            ->table($this->tableName)
            ->where('query', $query)
            ->first();
        if (!$items) {
            return response()->json(['message' => 'Item not found'], 404);
        }
        return response()->json([
            'data' => $items
        ], 200);
    }

    /**
     * Delete URL alias.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: xl.de
     * @urlParam id integer required Identifier of the entity to remove. Example: 55
     * @bodyParam content_type string required Must be either `category` or `product`. Example: category
     * @response 200 {
     *   "is_deleted": true,
     *   "deleted_count": 1
     * }
     * @response 404 {
     *   "message": "Item was not found"
     * }
     */
    public function delete(int $id, Request $request) {
        $validated = $request->validate([
            'content_type' => 'required|in:category,product',
        ]);
        if(in_array($validated['content_type'], ['category', 'product']))
            $query = "{$validated['content_type']}_id={$id}";
        $deleted = DB::connection($this->database)
            ->table($this->tableName)
            ->where('query', $query)
            ->delete();
        if(!$deleted) abort(404, "Item was not found");
        return response()->json([
            'is_deleted' => true,
            'deleted_count' => $deleted,
        ], 200);
    }
}
