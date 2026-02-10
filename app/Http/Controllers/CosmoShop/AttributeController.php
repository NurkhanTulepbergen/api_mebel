<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group CosmoShop
 * @subgroup Attribute
 */
class AttributeController extends Controller
{
    public $tableName = \TableName::Attribute->value;
    public $availableDatabases;
    public $database;
    public $pk = 'id';

    public function __construct(Request $request){
        $array = env('DB_ARRAY');
        $availableDatabases =  json_decode($array);
        $this->database = $request->header('database');
        if (!$this->database || !in_array($this->database, $availableDatabases)) {
            abort(400, "Database header is required. Expected one of the database values: ".implode(', ', $availableDatabases));
        }
    }

    public function create(Request $request) {
        $validated = $request->validate([
            'level' => 'in:1,2,3',
            'label' => 'required|string|max:100',
            'sort' => 'required|integer',
            'refid' => 'integer',
            'google_category' => 'string|max:255',
            'root_display_group' => 'integer',
            'display_type' => 'string|max:20',
        ]);

        $id = DB::connection($this->database)
            ->table($this->tableName)
            ->insertGetId($validated);
        $item = DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->get();

        return response()->json([
            'message' => 'created',
            'data' => $item
        ], 201);
    }

    public function update(int $id, Request $request) {
        $validated = $request->validate([
            'level' => 'in:1,2,3',
            'label' => 'string|max:100',
            'sort' => 'integer',
            'refid' => 'integer',
            'google_category' => 'string|max:255',
            'root_display_group' => 'integer',
            'display_type' => 'string|max:20',
        ]);
        $db = DB::connection($this->database);

        $oldItem = $db
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->first(array_keys($validated));
        $affected = $db
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->update($validated);
        if ($affected == 0) return response()->json(['message' => 'Item not found'], 404);
        $db->commit();
        $item = $db
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->first(array_keys($validated));
        return response()->json([
            'message' => 'updated',
            'old_item' => $oldItem,
            'new_item' => $item,
        ], 200);
    }

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

    public function read(int $id, Request $request) {
        $item = DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->first();

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        return response()->json([
            'data' => $item
        ], 200);
    }

    public function delete(int $id, Request $request) {
        DB::connection($this->database)->table($this->tableName)->where($this->pk, $id)->delete();
        return response()->json([
            'message' => 'Item was deleted'
        ], 200);
    }
}
