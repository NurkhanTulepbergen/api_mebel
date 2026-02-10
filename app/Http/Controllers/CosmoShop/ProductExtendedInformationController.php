<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group CosmoShop
 * @subgroup ProductExtendedInformation
 */
class ProductExtendedInformationController extends Controller
{
    public $tableName = \TableName::ProductExtendedInformation->value; // should be 'lieferanteninfo'
    public $pk = 'lieferanteninfoid';
    public $articleFk = 'artikelid';
    public $mapping = [
        'extended_info_id' => 'lieferanteninfoid',
        'article_id'       => 'artikelid',
        'manufacturer_id'  => 'lieferantid',
        'manufacturer_sku' => 'liefernr',
        'purchase_price'   => 'preis_ek',
        'sale_status'      => 'abverkauf',
        'delivery_time'    => 'lieferzeit',
        'delivery_date'    => 'lieferdatum',
    ];
    public $database;
    public $flippedMapping;

    public function __construct(Request $request)
    {
        $available = json_decode(env('DB_ARRAY'));
        $this->database = $request->header('database');

        if (!$this->database || !in_array($this->database, $available)) {
            abort(400, "Database header is required. Expected one of: " . implode(', ', $available));
        }

        $this->flippedMapping = array_flip($this->mapping);
    }

    private function changeFields($collection)
    {
        $mapped = [];
        foreach ($this->flippedMapping as $dbField => $apiField) {
            if (isset($collection->$dbField)) {
                $mapped[$apiField] = $collection->$dbField;
            }
        }
        return $mapped;
    }

    // -------------------------------------------
    // READ (one item)
    // -------------------------------------------
    public function read($id)
    {
        $item = DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->first();

        if (!$item) return response()->json(['message' => 'Item not found'], 404);

        return response()->json(['data' => $this->changeFields($item)], 200);
    }

    // -------------------------------------------
    // READ BY ARTICLE ID
    // -------------------------------------------
    public function readByArticle($articleId)
    {
        $items = DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->articleFk, $articleId)
            ->get();

        $mapped = $items->map(fn($i) => $this->changeFields($i));

        return response()->json(['data' => $mapped], 200);
    }

    // -------------------------------------------
    // CREATE
    // -------------------------------------------
    public function create(Request $request)
    {
        $validated = $request->validate([
            'article_id'       => 'required|integer',
            'manufacturer_id'  => 'integer',
            'manufacturer_sku' => 'string',
            'purchase_price'   => 'numeric',
            'sale_status'      => 'integer',
            'delivery_time'    => 'integer',
            'delivery_date'    => 'string',
        ]);

        $mapped = [];
        foreach ($validated as $key => $value) {
            if (isset($this->mapping[$key])) {
                $mapped[$this->mapping[$key]] = $value;
            }
        }

        $id = DB::connection($this->database)
            ->table($this->tableName)
            ->insertGetId($mapped);

        $item = DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->first();

        return response()->json([
            'message' => 'created',
            'data' => $this->changeFields($item)
        ], 201);
    }

    // -------------------------------------------
    // UPDATE
    // -------------------------------------------
    public function update($id, Request $request)
    {
        $validated = $request->validate([
            'manufacturer_id'  => 'integer',
            'manufacturer_sku' => 'string',
            'purchase_price'   => 'numeric',
            'sale_status'      => 'integer',
            'delivery_time'    => 'integer',
            'delivery_date'    => 'string',
        ]);

        $db = DB::connection($this->database);

        $mapped = [];
        foreach ($validated as $key => $value) {
            if (isset($this->mapping[$key])) {
                $mapped[$this->mapping[$key]] = $value;
            }
        }

        $oldItem = $db
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->first();

        $affected = $db
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->update($mapped);

        if ($affected == 0) return response()->json(['message' => 'Item not found'], 404);

        $newItem = $db
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->first();

        return response()->json([
            'message'   => 'updated',
            'old_item'  => $this->changeFields($oldItem),
            'new_item'  => $this->changeFields($newItem),
        ], 200);
    }

    // -------------------------------------------
    // DELETE
    // -------------------------------------------
    public function delete($id)
    {
        DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->delete();

        return response()->json(['message' => 'deleted'], 200);
    }
}
