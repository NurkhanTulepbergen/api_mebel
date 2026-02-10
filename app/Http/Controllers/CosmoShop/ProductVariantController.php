<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
/**
 * @group CosmoShop
 * @subgroup ProductVariant
 */
class ProductVariantController extends Controller
{
    public $tableName = 'shopartikelvarianten';
    public $pk = 'artikelvarianteid';
    public $mapping = [
        'variant_id'                => 'artikelvarianteid',
        'is_inactive'               => 'inaktiv',
        'execution_id'              => 'artikelausfuehrungid',
        'variant_number'            => 'variantenr',
        'variant_article_id'        => 'artikelidvariante',
        'sort_order'                => 'order',
        'custom_key'                => 'custom_key',
        'attribute_id'              => 'attributid',
        'second_price_multiplier'   => 'second_price_multiplier',
        'show_text_input'           => 'show_text_input',
        'custom_data'               => 'custom',
    ];
    public $flippedMapping;

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

    public function create(Request $request) {
        $validated = $request->validate([
            'is_inactive'             => 'boolean',
            'execution_id'            => 'integer',
            'variant_number'          => 'integer',
            'article_id'              => 'nullable|integer',
            'sort_order'              => 'integer',
            'custom_key'              => 'nullable|string',
            'attribute_id'            => 'integer',
            'second_price_multiplier' => 'numeric',
            'show_text_input'         => 'boolean',
            'custom_data'             => 'nullable|string',
        ]);
        $db = DB::connection($this->database);
        $mappedData = [];
        foreach ($validated as $key => $value) {
            if (isset($this->mapping[$key])) {
                $mappedData[$this->mapping[$key]] = $value;
            }
        }
        $id = $db->beginTransaction();
        $db->table($this->tableName)
            ->insertGetId($mappedData);
        $item = $db
            ->table($this->tableName)
            ->where($this->pk, $id)
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
            'is_inactive'             => 'boolean',
            'execution_id'            => 'integer',
            'variant_number'          => 'integer',
            'article_id'              => 'nullable|integer',
            'sort_order'              => 'integer',
            'custom_key'              => 'nullable|string',
            'attribute_id'            => 'integer',
            'second_price_multiplier' => 'numeric',
            'show_text_input'         => 'boolean',
            'custom_data'             => 'nullable|string',
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
            'data' => $mappedItems
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
