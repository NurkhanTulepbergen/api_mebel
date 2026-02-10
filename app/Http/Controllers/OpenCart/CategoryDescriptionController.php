<?php

namespace App\Http\Controllers\OpenCart;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group OpenCart
 * @subgroup CategoryDescription
 */
class CategoryDescriptionController extends Controller
{
    private $database;
    private $tableName = 'oc_category_description';

    public function __construct(Request $request){
        $array = env('DB_ARRAY');
        $availableDatabases =  json_decode($array);
        $this->database = $request->header('database');
        if (!$this->database || !in_array($this->database, $availableDatabases)) {
            abort(400, "Database header is required. Expected one of the database values: ".implode(', ', $availableDatabases));
        }
    }

    /**
     * Create category description.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: xl.de
     * @bodyParam category_id integer required Category identifier from OpenCart. Example: 15
     * @bodyParam language_id integer required Language identifier. Example: 1
     * @bodyParam name string required Public category name. Example: Sofas
     * @bodyParam description string Detailed description shown in the catalog. Example: Comfortable and modern sofas.
     * @bodyParam meta_title string required HTML meta title for the category page. Example: Sofas & Sectionals
     * @bodyParam meta_description string required HTML meta description for SEO. Example: Browse our collection of sofas.
     * @bodyParam meta_keyword string required Comma-separated SEO keywords. Example: sofa,living room,furniture
     * @response 201 {
     *   "message": "Item was created",
     *   "data": {
     *     "category_id": 15,
     *     "language_id": 1,
     *     "name": "Sofas",
     *     "description": "Comfortable and modern sofas.",
     *     "meta_title": "Sofas & Sectionals",
     *     "meta_description": "Browse our collection of sofas.",
     *     "meta_keyword": "sofa,living room,furniture"
     *   }
     * }
     * @response 502 {
     *   "message": "category description with that category_id and language already exists."
     * }
     */
    public function create(Request $request) {
        $validated = $request->validate([
            'category_id'       => 'required|integer',
            'language_id'       => 'required|integer',
            'name'              => 'required|string',
            'description'       => 'string',
            'meta_title'        => 'required|string',
            'meta_description'  => 'required|string',
            'meta_keyword'      => 'required|string',
        ]);
        $db = DB::connection($this->database);
        $isEsxists = $db->table($this->tableName)
            ->where('category_id', $validated['category_id'])
            ->where('language_id', $validated['language_id'])
            ->exists();
        if($isEsxists) {
            return response()->json([
                'message' => 'category description with that category_id and language already exists.',
            ], 502);
        }
        $db->beginTransaction();
        $db->table($this->tableName)
            ->insert($validated);
        $item = $db->table($this->tableName)
            ->where('category_id', $validated['category_id'])
            ->where('language_id', $validated['language_id'])
            ->first();
        $db->commit();
        return response()->json([
            'message' => 'Item was created',
            'data' => $item
        ], 201);
    }

    /**
     * Update category description.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: xl.de
     * @bodyParam category_id integer required Category identifier from OpenCart. Example: 15
     * @bodyParam language_id integer required Language identifier. Example: 1
     * @bodyParam name string required Public category name. Example: Sofas
     * @bodyParam description string Detailed description shown in the catalog. Example: Comfortable and modern sofas.
     * @bodyParam meta_title string required HTML meta title for the category page. Example: Sofas & Sectionals
     * @bodyParam meta_description string required HTML meta description for SEO. Example: Browse our collection of sofas.
     * @bodyParam meta_keyword string required Comma-separated SEO keywords. Example: sofa,living room,furniture
     * @response 200 {
     *   "message": "updated",
     *   "old_item": {
     *     "category_id": 15,
     *     "language_id": 1,
     *     "name": "Sofas"
     *   },
     *   "new_item": {
     *     "category_id": 15,
     *     "language_id": 1,
     *     "name": "Sofas"
     *   }
     * }
     * @response 404 scenario="Missing record" {
     *   "message": "Item not found"
     * }
     * @response 404 scenario="No changes" {
     *   "message": "Nothing changed"
     * }
     */
    public function update(Request $request) {
        $validated = $request->validate([
            'category_id'       => 'required|integer',
            'language_id'       => 'required|integer',
            'name'              => 'required|string',
            'description'       => 'string',
            'meta_title'        => 'required|string',
            'meta_description'  => 'required|string',
            'meta_keyword'      => 'required|string',
        ]);
        $db = DB::connection($this->database);
        $id = $validated['category_id'];
        $oldItem = $db
            ->table($this->tableName)
            ->where('category_id', $id)
            ->where('language_id', $validated['language_id'])
            ->first();
        if (!$oldItem) return response()->json(['message' => 'Item not found'], 404);
        $affected = $db
            ->table($this->tableName)
            ->where('category_id', $id)
            ->where('language_id', $validated['language_id'])
            ->update($validated);
            if (!$affected) return response()->json(['message' => 'Nothing changed'], 404);
        $db->commit();
        $newItem = $db
            ->table($this->tableName)
            ->where('category_id', $id)
            ->where('language_id', $validated['language_id'])
            ->first();
        return response()->json([
            'message' => 'updated',
            'old_item' => $oldItem,
            'new_item' => $newItem,
        ], 200);
    }

    /**
     * List category descriptions.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: xl.de
     * @queryParam page integer Page number of results to return. Example: 2
     * @response 200 {
     *   "data": {
     *     "current_page": 1,
     *     "data": [
     *       {
     *         "category_id": 15,
     *         "language_id": 1,
     *         "name": "Sofas"
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
            ->table('oc_category_description')
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
     * Show category descriptions by category.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: xl.de
     * @urlParam id integer required Category identifier to fetch. Example: 15
     * @response 200 {
     *   "data": [
     *     {
     *       "category_id": 15,
     *       "language_id": 1,
     *       "name": "Sofas"
     *     }
     *   ]
     * }
     * @response 404 {
     *   "message": "Item not found"
     * }
     */
    public function read(int $id, Request $request) {
        $items = DB::connection($this->database)
            ->table($this->tableName)
            ->where('category_id', $id)
            ->get();
        if (!$items) {
            return response()->json(['message' => 'Item not found'], 404);
        }
        return response()->json([
            'data' => $items
        ], 200);
    }

    /**
     * Delete category description.
     *
     * The route includes `/category-description/{id}/{language}`, but the request body still must provide `language_id`.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: xl.de
     * @urlParam id integer required Category identifier to delete from. Example: 15
     * @urlParam language integer required Language identifier as part of the route. Example: 1
     * @bodyParam language_id integer required Language identifier which will be deleted. Example: 1
     * @response 200 {
     *   "message": "1 items was deleted"
     * }
     * @response 404 {
     *   "message": "Item was not found"
     * }
     */
    public function delete($id, Request $request) {
        $validated = $request->validate([
            'language_id' => 'required|int'
        ]);
        $deleted = DB::connection($this->database)
            ->table($this->tableName)
            ->where('category_id', $id)
            ->where('language_id', $validated['language_id'])
            ->delete();
        if(!$deleted) abort(404, "Item was not found");
        return response()->json([
            'message' => $deleted.' items was deleted'
        ], 200);
    }
}
