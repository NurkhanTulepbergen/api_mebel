<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OnlyNeededDataController extends Controller
{

    public $tableName = 'only_needed_data_google';
    public $database;

    public function __construct(Request $request){
        $array = env('DB_ARRAY');
        $availableDatabases =  json_decode($array);
        $this->database = $request->header('database');
        if (!$this->database || !in_array($this->database, $availableDatabases)) {
            abort(400, "Database header is required. Expected one of the database values: ".implode(', ', $availableDatabases));
        }
        if(!str_contains($this->database, 'jv')) abort(400, 'Your current database is XL. You cant use this endpoint here');
    }

    function create(Request $request) {
        $validated = $request->validate([
            'ean'      => 'required|string|max:13',
            'title'     => 'required|string|max:250',
            'price'    => 'required|decimal:0,10',
            'size'     => 'sometimes|nullable|string|max:100',
            'color'    => 'sometimes|nullable|string|max:100',
            'material' => 'sometimes|nullable|string|max:100',
        ]);
        $db = DB::connection($this->database)->table($this->tableName);
        $isDublicate = $db
            ->where('ean', $validated['ean'])
            ->where('title', $validated['title'])
            ->exists();

        if($isDublicate) {
            return response()->json([
                'message' => 'Product with that name and ean already exists',
                'ean' => $validated['ean'],
                'title' => $validated['title'],
            ], 422);
        }

        $isCreated = $db->insert($validated);


        return response()->json([
            'message' => $isCreated ? 'created' : 'not created',
            'is_created' => $isCreated,
        ], $isCreated ? 201 : 422);
    }

    function update(Request $request) {
        $validated = $request->validate([
            'ean'      => 'required|string|max:13',
            'title'     => 'required|string',
            'size'     => 'sometimes|nullable|string',
            'color'    => 'sometimes|nullable|string',
            'material' => 'sometimes|nullable|string',
            'price'    => 'sometimes|decimal:0,10'
        ]);
        $db = DB::connection($this->database)->table($this->tableName);
        $oldItem = $db
            ->where('ean', $validated['ean'])
            ->where('title', $validated['title'])
            ->get();

        $db->where('ean', $validated['ean'])
            ->where('title', $validated['title'])
            ->update($validated);

        $updatedItem = $db
            ->where('ean', $validated['ean'])
            ->where('title', $validated['title'])
            ->get();

        return response()->json([
            'message' => 'updated',
            'old_item' => $oldItem,
            'updated_item' => $updatedItem,
        ], 200);
    }

    function delete(Request $request) {
        $validated = $request->validate([
            'ean'      => 'required|string|max:13',
            'title'     => 'required|string|max:250',
        ]);
        $db = DB::connection($this->database)->table($this->tableName);
        $item = $db
            ->where('ean', $validated['ean'])
            ->where('title', $validated['title'])
            ->first();

        if(!$item) {
            return response()->json([
                'message' => 'Product with that name and ean doesnt exist',
                'ean' => $validated['ean'],
                'title' => $validated['title'],
            ], 404);
        }

        $db->where('ean', $validated['ean'])
            ->where('title', $validated['title'])
            ->delete();

        return response()->json([
            'message' => 'deleted',
            'item' => $item,
        ], 200);
    }

    function showByNameAndEan(Request $request) {
        $validated = $request->validate([
            'ean'      => 'required|string|max:13',
            'title'    => 'required|string|max:250',
        ]);
        $db = DB::connection($this->database)->table($this->tableName);
        $item = $db
            ->where('ean', $validated['ean'])
            ->where('title', $validated['title'])
            ->first();

        if(!$item) {
            return response()->json([
                'message' => 'Product with that title and ean doesnt exist',
                'ean' => $validated['ean'],
                'title' => $validated['title'],
            ], 404);
        }

        return response()->json([
            'message' => 'found',
            'item' => $item,
        ], 200);
    }

    function show(int $id) {
        $db = DB::connection($this->database)->table($this->tableName);
        $item = $db
            ->where('article_id', $id)
            ->firstOrFail();

        return response()->json([
            'message' => 'found',
            'item' => $item,
        ], 200);
    }

    function paginate(Request $request) {
        $perPage = 100;
        if($request->has('per_page'))
            if($request->per_page > 250)
                $perPage = 250;
            else
                $perPage = $request->per_page;

        $items = DB::connection($this->database)
            ->table($this->tableName)
            ->select('article_id', 'title', 'price', 'size', 'color', 'material', 'ean')
            ->paginate($perPage);

        return response()->json([
            'message' => 'found',
            'items' => $items,
        ], 200);
    }

    function findManyIds(Request $request) {
        $validated = $request->validate([
            'article_ids' => 'required|array',
            'article_ids.*' => 'required|integer'
        ]);

        $items = DB::connection($this->database)
            ->table($this->tableName)
            ->whereIn('article_id', $validated['article_ids'])
            ->get()
            ->keyBy('article_id');


        return response()->json([
            'items' => $items,
        ], 200);
    }

}
