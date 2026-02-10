<?php

namespace App\Http\Controllers\OpenCart;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

/**
 * @group OpenCart
 * @subgroup ProductImage
 */
class ProductImageController extends Controller
{
    public $availableDatabases;
    public $database;
    private $table = 'oc_product_image';

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
     * Create product gallery.
     *
     * Existing product images are removed, then the provided list is inserted with sequential sort order.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: xl.de
     * @bodyParam product_id integer required Product identifier. Example: 501
     * @bodyParam images array required Images listed in display order.
     * @bodyParam images[].* string Image path relative to catalog root. Example: catalog/products/501/main.jpg
     * @response 201 true
     */
    public function create(Request $request) {
        $validated = $request->validate([
            'product_id'    => 'required|integer|min:1|max:99999999999',
            'images'        => 'required|array|min:1',
            'images.*'      => 'required|string|max:255',
        ]);

        $images = $validated['images'];

        DB::connection($this->database)
            ->table('oc_product')
            ->where('product_id', $validated['product_id'])
            ->update([
                'image' => $images[0],
            ]);

        array_shift($images);

        $rows = [];
        $order = 0;
        
        foreach($images as $image) {
            $rows[] = [
                'product_id'    => $validated['product_id'],
                'image'         => $image,
                'sort_order'    => $order,
            ];
            $order++;
        }

        DB::connection($this->database)
            ->table($this->table)
            ->where('product_id', $validated['product_id'])
            ->delete();

        $isCreated = DB::connection($this->database)
            ->table($this->table)
            ->insert($rows);

        return response()->json($isCreated, 201);
    }

    /**
     * Get product gallery.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: xl.de
     * @urlParam id integer required Product identifier to fetch. Example: 501
     * @response 200 [
     *   {
     *     "product_image_id": 10,
     *     "product_id": 501,
     *     "image": "catalog/products/501/main.jpg",
     *     "sort_order": 0
     *   }
     * ]
     */
    public function read($id) {
        validator(['id' => $id], [
            'id' => 'required|integer|min:1|max:99999999999',
        ])->validate();

        $items = DB::connection($this->database)
            ->table($this->table)
            ->where('product_id', $id)
            ->orderBy('sort_order', 'asc')
            ->get();

        return response()->json($items);
    }

    /**
     * Delete product gallery.
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
