<?php

namespace App\Http\Controllers\OpenCart;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group OpenCart
 * @subgroup ProductToCategory
 */
class ProductToCategoryController extends Controller
{
    public $availableDatabases;
    public $database;
    private $table = 'oc_product_to_category';

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
     * Replace product categories.
     *
     * Clears existing relations and assigns the provided main and additional categories.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: xl.de
     * @bodyParam product_id integer required Product identifier. Example: 501
     * @bodyParam main_category integer required Main category identifier. Example: 30
     * @bodyParam categories array Additional categories to attach. Example: [42,55]
     * @bodyParam categories[].* integer Category identifier. Example: 42
     * @response 201 {
     *   "is_created": true
     * }
     */
    public function create(Request $request) {
        $validated = $request->validate([
            'product_id'    => 'required|integer|min:1|max:99999999999',
            'main_category' => 'required|integer|min:1|max:99999999999',
            'categories'        => 'array',
            'categories.*'      => 'required|integer|min:1|max:99999999999',
        ]);

        $rows = [];

        $rows[] = [
            'product_id'    => $validated['product_id'],
            'category_id'   => $validated['main_category'],
            'main_category' => 1,
        ];
        
        $categories = $validated['categories'] ?? [];
        foreach($categories as $category) {
            $rows[] = [
                'product_id'    => $validated['product_id'],
                'category_id'   => $category,
                'main_category' => 0,
            ];
        }
        
        DB::connection($this->database)
            ->table($this->table)
            ->where('product_id', $validated['product_id'])
            ->delete();

        $isCreated = DB::connection($this->database)
            ->table($this->table)
            ->insert($rows);

        return response()->json(['is_created' => $isCreated], 201);
    }

    /**
     * Get product categories.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: xl.de
     * @urlParam id integer required Product identifier to inspect. Example: 501
     * @response 200 [
     *   {
     *     "category_id": 30,
     *     "is_main": true
     *   },
     *   {
     *     "category_id": 42,
     *     "is_main": false
     *   }
     * ]
     * @response 404 {
     *   "message": "item not found"
     * }
     */
    public function read($id) {
        validator(['id' => $id], [
            'id' => 'required|integer|min:1|max:99999999999',
        ])->validate();
        
        $data = [];

        $items = DB::connection($this->database)
            ->table($this->table)
            ->where('product_id', $id)
            ->orderBy('main_category', 'desc')
            ->get();
        
        if(count($items) == 0)
            return response()->json(['message' => 'item not found'], 404);

        foreach($items as $item) {
            $data[] = [
                'category_id'   => $item->category_id,
                'is_main'       => $item->main_category == 1,
            ];
        }
        
        return response()->json($data);
    }

    /**
     * Delete product categories.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: xl.de
     * @urlParam id integer required Product identifier to clear. Example: 501
     * @response 200 {
     *   "is_deleted": true,
     *   "deleted_count": 3
     * }
     * @response 400 {
     *   "is_deleted": false
     * }
     */
    public function delete($id) {
        validator(['id' => $id], [
            'id' => 'required|integer|min:1|max:99999999999',
        ])->validate();
        $deleted = DB::connection($this->database)
            ->table($this->table)
            ->where('product_id', $id)
            ->delete();
        if($deleted == 0) {
            return response()->json([
                'is_deleted' => false
            ], 400);
        }
        return response()->json([
            'is_deleted' => true,
            'deleted_count' => $deleted,
        ], 200);
    }
}
