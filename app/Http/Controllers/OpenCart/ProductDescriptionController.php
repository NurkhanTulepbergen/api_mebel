<?php

namespace App\Http\Controllers\OpenCart;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group OpenCart
 * @subgroup ProductDescription
 */
class ProductDescriptionController extends Controller
{
    public $availableDatabases;
    public $database;
    private $table = 'oc_product_description';

    public function __construct(Request $request){
        $array = env('DB_ARRAY');
        $availableDatabases =  json_decode($array);
        $this->database = $request->header('database');
        if (!$this->database || !in_array($this->database, $availableDatabases)) {
            abort(400, "database header is required. Expected one of the database values: ".implode(', ', $availableDatabases));
        }
        if(str_contains($this->database, 'jv')) abort(400, 'your current database is JV. You cant use this endpoint here');
    }

    /**
     * Create product description.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: xl.de
     * @bodyParam product_id integer required Product identifier. Example: 501
     * @bodyParam language_id integer required Language identifier. Example: 1
     * @bodyParam name string required Product title. Example: Oak Dining Table
     * @bodyParam description string required Full description text. Example: Solid oak table for six persons.
     * @bodyParam tag string required Comma separated keywords. Example: dining,table,oak
     * @bodyParam meta_title string required Meta title for SEO. Example: Oak Dining Table
     * @bodyParam meta_description string required Meta description for SEO. Example: Premium oak dining table for modern interiors.
     * @bodyParam meta_keyword string required Meta keywords for SEO. Example: oak,dining,furniture
     * @response 201 {
     *   "product_id": 501,
     *   "language_id": 1,
     *   "name": "Oak Dining Table",
     *   "description": "Solid oak table for six persons."
     * }
     * @response 409 {
     *   "message": "item with that product_id and language_id already exists"
     * }
     */
    public function create(Request $request) {
        $validated = $request->validate([
            'product_id'       => 'required|integer',
            'language_id'      => 'required|integer',
            'name'             => 'required|string|max:255',
            'description'      => 'required|string',
            'tag'              => 'required|string',
            'meta_title'       => 'required|string',
            'meta_description' => 'required|string',
            'meta_keyword'     => 'required|string',
        ]);

        try {
            DB::connection($this->database)
                ->table($this->table)
                ->insert($validated);

            return response()->json(['is_created' => true], 201);

        } catch (QueryException $e) {
            if ($e->errorInfo[1] == 1062) {
                return response()->json([
                    'message' => 'item with that product_id and language_id already exists',
                ], 409);
            }

            throw $e;
        }
    }


    /**
     * Paginate product descriptions.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: xl.de
     * @queryParam language_id integer Filter entries by language identifier. Example: 1
     * @queryParam page integer Page number to return. Example: 2
     * @response 200 {
     *   "current_page": 1,
     *   "data": [
     *     {
     *       "product_id": 501,
     *       "language_id": 1,
     *       "name": "Oak Dining Table"
     *     }
     *   ],
     *   "per_page": 100,
     *   "total": 1
     * }
     */
    public function paginate(Request $request) {
        $validated = $request->validate([
            'language_id' => 'sometimes|integer',
        ]);
        $query = DB::connection($this->database)
            ->table($this->table);

        if($request->has('language_id'))
            $query = $query->where('language_id', $validated['language_id']);
            
        $items = $query->orderBy('product_id')
            ->paginate(100);

        return response($items);
    }

    /**
     * Read product description.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: xl.de
     * @bodyParam product_id integer required Product identifier. Example: 501
     * @bodyParam language_id integer required Language identifier. Example: 1
     * @response 200 {
     *   "product_id": 501,
     *   "language_id": 1,
     *   "name": "Oak Dining Table"
     * }
     * @response 404 {
     *   "message": "No query results for model [oc_product_description]."
     * }
     */
    public function read(Request $request) {
        $validated = $request->validate([
            'product_id'    => 'required|integer',
            'language_id'   => 'required|integer',
        ]);
        $item = DB::connection($this->database)
            ->table($this->table)
            ->where('product_id', $validated['product_id'])
            ->where('language_id', $validated['language_id'])
            ->firstOrFail();

        return response()->json($item);
    }

    /**
     * Update product description.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: xl.de
     * @bodyParam product_id integer required Product identifier. Example: 501
     * @bodyParam language_id integer required Language identifier. Example: 1
     * @bodyParam name string Product title. Example: Oak Dining Table
     * @bodyParam description string Full description text. Example: Solid oak table for six persons.
     * @bodyParam tag string Comma separated keywords. Example: dining,table,oak
     * @bodyParam meta_title string Meta title for SEO. Example: Oak Dining Table
     * @bodyParam meta_description string Meta description for SEO. Example: Premium oak dining table for modern interiors.
     * @bodyParam meta_keyword string Meta keywords for SEO. Example: oak,dining,furniture
     * @response 200 {
     *   "is_updated": true
     * }
     * @response 400 {
     *   "is_updated": false
     * }
     */
    public function update(Request $request) {
        $validated = $request->validate([
            'product_id'       => 'required|integer',
            'language_id'      => 'required|integer',
            'name'             => 'string|max:255',
            'description'      => 'string',
            'tag'              => 'string',
            'meta_title'       => 'string',
            'meta_description' => 'string',
            'meta_keyword'     => 'string',
        ]);

        $affected = DB::connection($this->database)
            ->table($this->table)
            ->where('product_id', $validated['product_id'])
            ->where('language_id', $validated['language_id'])
            ->update($validated);
        $status = $affected > 0 ? 200 : 400;
        return response()->json([
            'is_updated' => $affected > 0,
        ], $status);
    }

    /**
     * Delete product description.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: xl.de
     * @bodyParam product_id integer required Product identifier. Example: 501
     * @bodyParam language_id integer required Language identifier. Example: 1
     * @response 200 {
     *   "is_deleted": true
     * }
     * @response 400 {
     *   "is_deleted": false
     * }
     */
    public function delete(Request $request) {
        $validated = $request->validate([
            'product_id'    => 'required|integer',
            'language_id'   => 'required|integer',
        ]);
        $deleted = DB::connection($this->database)
            ->table($this->table)
            ->where('product_id', $validated['product_id'])
            ->where('language_id', $validated['language_id'])
            ->delete();
        if($deleted == 0) {
            return response()->json([
                'is_deleted' => false
            ], 400);
        }
        return response()->json([
            'is_deleted' => true
        ], 200);
    }
}
