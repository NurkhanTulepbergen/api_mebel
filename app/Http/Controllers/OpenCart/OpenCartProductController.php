<?php

namespace App\Http\Controllers\OpenCart;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpenCartProductController extends Controller
{
    public $availableDatabases;
    public $database;

    public function __construct(Request $request){
        $array = env('DB_ARRAY');
        $availableDatabases =  json_decode($array);
        $this->database = $request->header('database');
        if (!$this->database || !in_array($this->database, $availableDatabases)) {
            abort(400, "Database header is required. Expected one of the database values: ".implode(', ', $availableDatabases));
        }
        if(str_contains($this->database, 'jv')) {
            return response()->json([
                'message' => 'Your current database is JV. You cant use this endpoint here'
            ], 401);
        }
    }

    public function getProductWithImages(Request $request) {
        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
        ]);
        $item = DB::connection($this->database)
            ->table('oc_product')
            ->where('product_id', $validated['product_id'])
            ->first();
        if (!$item) return response()->json(['message' => 'Item not found'], 404);
        $item->images = DB::connection($this->database)
            ->table('oc_product_image')
            ->where('product_id', $validated['product_id'])
            ->get();

        return response()->json($item, 200);
    }
}
