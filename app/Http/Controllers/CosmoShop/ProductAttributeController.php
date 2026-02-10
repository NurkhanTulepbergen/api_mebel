<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group CosmoShop
 * @subgroup ProductAttribute
 */
class ProductAttributeController extends Controller
{
    public $tableName = \TableName::ProductAttribute->value;
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
            'article_id' => 'required|integer',
            'article_base_id' => 'required|integer',
            'attribute_id' => 'required|integer',
            'updated_at' => 'date'
        ]);
        $validated['updated_at'] = now();
        $mappedData = [];
        foreach ($validated as $key => $value) {
            if (isset($fieldMapping[$key])) {
                $mappedData[$this->flippedMapping[$key]] = $value;
            }
        }
        DB::connection($this->database)
            ->table($this->tableName)
            ->insert($mappedData);
        $item = DB::connection($this->database)
            ->table($this->tableName)
            ->where('artikelid', $validated['article_id'])
            ->first();

        return response()->json([
            'message' => 'created',
            'data' => $this->changeFields($item)
        ], 201);
    }

    public function update(int $id, Request $request) {
        $validated = $request->validate([
            'article_base_id' => 'required|integer|exists:shopartikel_attribute,artikelbaseid',
            'attribute_id' => 'required|integer|exists:shopartikel_attribute,attributid',
            'updated_at' => 'nullable|date'
        ]);
        $validated['updated_at'] = now();
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
