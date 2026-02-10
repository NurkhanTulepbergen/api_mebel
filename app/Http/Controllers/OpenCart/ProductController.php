<?php

namespace App\Http\Controllers\OpenCart;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Subgroup;
use Knuckles\Scribe\Attributes\Unauthenticated;

use App\Http\Traits\ChangePriceTrait;

#[Authenticated]
#[Group("OpenCart")]
#[Subgroup("Product")]
class ProductController extends Controller
{
    use ChangePriceTrait;
    public $availableDatabases;
    public $database;
    private $table = 'oc_product';

    private $availableDatabaseFields = [
            'product' => [
                'product_id',
                'model',
                'sku',
                'upc',
                'ean',
                'jan',
                'isbn',
                'mpn',
                'location',
                'quantity',
                'stock_status_id',
                'image',
                'manufacturer_id',
                'shipping',
                'price',
                'points',
                'tax_class_id',
                'date_available',
                'weight',
                'weight_class_id',
                'length',
                'width',
                'height',
                'length_class_id',
                'subtract',
            ],
            'product_description' => [
                'product_id',
                'language_id',
                'name',
                'description',
                'tag',
                'meta_title',
                'meta_description',
                'meta_keyword',
            ],
            'manufacturer' => [
                'manufacturer_id',
                'name',
                'delivery_time',
                'image',
                'sort_order',
            ],
            'category_description' => [
                'category_id',
                'language_id',
                'name',
                'description',
                'meta_title',
                'meta_description',
                'meta_keyword',
            ],
            'product_special' => [
                'price',
            ],
            'url_alias' => [
                'keyword',
            ],
            'google_category' => [
                'name',
            ],
            'product_image' => [
                'image',
            ],
        ];

    public function __construct(Request $request){
        $array = env('DB_ARRAY');
        $availableDatabases =  json_decode($array);
        $this->database = $request->header('database');
        if (!$this->database || !in_array($this->database, $availableDatabases)) {
            abort(400, "Database header is required. Expected one of the database values: ".implode(', ', $availableDatabases));
        }
        if(str_contains($this->database, 'jv')) abort(400, 'Your current database is JV. You cant use this endpoint here');
    }

    /**
     * Get product with images.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: opencart_default
     * @bodyParam product_id integer required Product identifier to fetch. Example: 123
     * @response 200 {
     *   "product_id": 123,
     *   "model": "SKU-123",
     *   "images": [
     *     {
     *       "product_image_id": 10,
     *       "image": "catalog/products/img.jpg"
     *     }
     *   ]
     * }
     * @response 404 {
     *   "message": "Item not found"
     * }
     */
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

    /**
     * Get custom product output.
     *
     * Returns a mapping keyed by the requested product attribute with the selected value(s) from related tables.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: opencart_default
     * @bodyParam key string required Product table column used to match entries. Example: product_id
     * @bodyParam value array required Values for the key column. Example: [123,456]
     * @bodyParam table string Table name without the `oc_` prefix; defaults to `product`. Example: product_description
     * @bodyParam output_field string required Column to return from the chosen table. Example: name
     * @bodyParam language_id integer Language identifier required when requesting description tables. Example: 1
     * @response 200 {
     *   "data": {
     *     "123": "Example name",
     *     "456": [
     *       "First value",
     *       "Second value"
     *     ]
     *   }
     * }
     * @response 422 scenario="Missing language" {
     *   "message": "language_id is required in tables [product_description, category_description]"
     * }
     * @response 422 scenario="Unknown table" {
     *   "message": "table 'foo' is not found",
     *   "available_tables": []
     * }
     */
    public function withCustomOutput(Request $request) {
        $validated = $request->validate([
            'key'           => 'required|string',
            'value'         => 'required|array',
            'table'         => 'sometimes|string',
            'output_field'  => 'required|string',
            'language_id'      => 'sometimes|integer',
        ]);

        if(!$request->has('table'))
            $validated['table'] = 'product';
        $tablesWithLanguage = ['product_description', 'category_description'];
        if(in_array($validated['table'], $tablesWithLanguage) && !$request->has('language_id'))
            return response()->json(['message' => "language_id is required in tables [".join(', ',$tablesWithLanguage).']'], 422);

        if(!array_key_exists($validated['table'], $this->availableDatabaseFields)) {
            return response()->json([
                    'message' => "table '{$validated['table']}' is not found",
                    'available_tables' => $this->availableDatabaseFields
            ], 422);
        }

        $validated['table'] = "oc_{$validated['table']}";

        $db = DB::connection($this->database)
            ->table('oc_product');
        if($validated['table'] == 'oc_product')
            $products = $db
                ->whereIn($validated['key'], $validated['value'])
                ->get(["{$validated['key']}", "{$validated['output_field']}"]);
        elseif($validated['table'] == 'oc_url_alias')
            $products = $db
                ->join($validated['table'],
                    DB::raw("CONCAT('product_id=', oc_product.product_id)"),
                    '=',
                    'oc_url_alias.query'
                )
                ->whereIn($validated['key'], $validated['value'])
                ->get(["oc_product.{$validated['key']}", "{$validated['table']}.{$validated['output_field']}"]);
        elseif($validated['table'] == 'oc_google_category')
            $products = $db
                ->join('oc_product_to_category', 'oc_product_to_category.product_id', '=', 'oc_product.product_id')
                ->join('oc_google_merchant_category', 'oc_google_merchant_category.category_id', '=', 'oc_product_to_category.category_id')
                ->join('oc_google_product_merchant_category', 'oc_google_product_merchant_category.google_merchant_category_id', '=', 'oc_google_merchant_category.google_merchant_category_id')
                ->where('oc_product_to_category.main_category', 1)
                ->whereIn("oc_product.{$validated['key']}", $validated['value'])
                ->get(["oc_product.{$validated['key']}", "oc_google_product_merchant_category.google_merchant_category_name as {$validated['output_field']}"]);
        elseif($validated['table'] == 'oc_category_description')
            $products = $db
                ->join('oc_product_to_category', 'oc_product_to_category.product_id', '=', 'oc_product.product_id')
                ->join('oc_category_description', 'oc_category_description.category_id', '=', 'oc_product_to_category.category_id')
                ->where('oc_product_to_category.main_category', 1)
                ->whereIn("oc_product.{$validated['key']}", $validated['value'])
                ->get(["oc_product.{$validated['key']}", "{$validated['table']}.{$validated['output_field']}"]);
        elseif(in_array($validated['table'], ['oc_product_description', 'oc_product_special']))
            $products = $db
                ->join($validated['table'], "{$validated['table']}.product_id", '=', 'oc_product.product_id')
                ->whereIn("oc_product.{$validated['key']}", $validated['value'])
                ->get(["oc_product.{$validated['key']}", "{$validated['table']}.{$validated['output_field']}"]);
        elseif($validated['table'] == 'oc_manufacturer')
            $products = $db
                ->join($validated['table'], "{$validated['table']}.manufacturer_id", '=', 'oc_product.manufacturer_id')
                ->whereIn("oc_product.{$validated['key']}", $validated['value'])
                ->get(["oc_product.{$validated['key']}", "{$validated['table']}.{$validated['output_field']}"]);
        elseif($validated['table'] == 'oc_product_image')
            $products = $db
                ->join($validated['table'], "{$validated['table']}.product_id", '=', 'oc_product.product_id')
                ->whereIn("oc_product.{$validated['key']}", $validated['value'])
                ->orderBy("{$validated['table']}.sort_order", 'asc')
                ->get(["oc_product.{$validated['key']}", "{$validated['table']}.{$validated['output_field']}"]);

        $data = [];

        foreach ($products as $row) {
                $k = $row->{$validated['key']};
                $v = $row->{$validated['output_field']};

                if (isset($data[$k])) {
                    // Уже есть значение, превращаем в массив если нужно
                    if (!is_array($data[$k])) {
                        $data[$k] = [$data[$k]];
                    }
                    $data[$k][] = $v;
                } else {
                    // Первый раз встречаем ключ
                    $data[$k] = $v;
                }
            }
        return response()->json(['data' => $data]);
    }

    /**
     * View product.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: opencart_default
     * @urlParam id integer required Product identifier to view. Example: 123
     * @response 200 {
     *   "product_id": 123,
     *   "model": "SKU-123"
     * }
     * @response 404 {
     *   "message": "No query results for model [oc_product]."
     * }
     */
    public function view($id) {
        validator(['id' => $id], [
            'id' => 'required|integer|min:1|max:99999999999',
        ])->validate();

        $item = DB::connection($this->database)
            ->table('oc_product')
            ->where('product_id', $id)
            ->firstOrFail();

        return response()->json($item);
    }

    /**
     * Paginate products.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: opencart_default
     * @queryParam per_page integer Results per page (max 250). Example: 50
     * @queryParam page integer Page number to return. Example: 3
     * @queryParam is_active integer Filter by `status` value (1 or 0). Example: 1
     * @response 200 {
     *   "current_page": 1,
     *   "data": [
     *     {
     *       "product_id": 123,
     *       "model": "SKU-123"
     *     }
     *   ],
     *   "per_page": 100,
     *   "total": 1
     * }
     */
    public function paginate(Request $request) {
        $perPage = 100;
        if($request->has('per_page'))
            $perPage = $request->per_page > 250 ? 250 : $request->per_page;

        if($request->has('is_active')) {
            $items = DB::connection($this->database)
                ->table('oc_product')
                ->where('status', $request->is_active)
                ->paginate($perPage);
        } else {
            $items = DB::connection($this->database)
                ->table('oc_product')
                ->paginate($perPage);
        }
        return response()->json($items);
    }

    /**
     * List products updated after date.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: opencart_default
     * @bodyParam updated_at string required Date in `d-m-Y` format. Example: 01-01-2024
     * @response 200 {
     *   "count": 2,
     *   "data": [
     *     123,
     *     456
     *   ]
     * }
     */
    public function getProductsUpdatedAfter(Request $request) {
        $validated = $request->validate([
            'updated_at' => [
                'required',
                'date_format:d-m-Y',     // указываем формат: день-месяц-год
                'before_or_equal:today', // проверка, что дата <= сегодняшней
            ],
        ]);

        $updatedAt = Carbon::createFromFormat('d-m-Y', $validated['updated_at'])->startOfDay();

        $ids = DB::connection($this->database)
            ->table('oc_product')
            ->where('date_modified', '>', $updatedAt)
            ->get()
            ->pluck('product_id');

        return response()->json([
            'count' => count($ids),
            'data' => $ids,
        ], 200);

    }

    /**
     * Custom output helper.
     *
     * Shows guidance on how to use the `withCustomOutput` endpoint and lists available tables.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: opencart_default
     * @response 200 {
     *   "data": {
     *     "key": "Ключ значение. Может быть значение только из таблицы продукта",
     *     "value": "Значения. Принимаются только массивом"
     *   },
     *   "available_tables": []
     * }
     */
    public function withCustomOutputHelp(Request $request) {
        $data = [
            'key' => 'Ключ значение. Может быть значение только из таблицы продукта',
            'value' => 'Значения. Принимаются только массивом',
            'table' => 'Название таблицы для вывода. Если нужно значение корня, то вставлять не обязательно',
            'output_field' => 'Название значений которые хотим получить',
        ];

        return response()->json([
            'data' => $data,
            'available_tables' => $this->availableDatabaseFields
        ]);
    }

    /**
     * Resolve product IDs.
     *
     * Matches provided values to product IDs and reports stats on findings.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: opencart_default
     * @bodyParam key string required Column in `oc_product` to query. Example: sku
     * @bodyParam value array required Unique values for the given key. Example: ["ABC-01","ABC-02"]
     * @response 200 {
     *   "stats": {
     *     "requested": 2,
     *     "found": 1,
     *     "not_found": 1
     *   },
     *   "found": {
     *     "ABC-01": 123
     *   },
     *   "not_found": [
     *     "ABC-02"
     *   ]
     * }
     * @response 400 scenario="JV database" {
     *   "message": "Your current database is JV. You cant use this endpoint here"
     * }
     * @response 404 {
     *   "message": "Item was not found"
     * }
     */
    public function getId(Request $request) {
        $validated = $request->validate([
            'key' => 'required|string',
            'value' => 'required|array'
        ]);
        $validated['value'] = array_unique($validated['value']);
        if(str_contains($this->database, 'jv')) {
            return response()->json([
                'message' => 'Your current database is JV. You cant use this endpoint here'
            ], 400);
        }
        $products = DB::connection($this->database)->table('oc_product')->whereIn($validated['key'], $validated['value'])->pluck('product_id', $validated['key'])->toArray();

        if(!$products) {
            return response()->json([
                'message' => 'Item was not found'
            ], 404);
        }
        $notFound = [];
        if(sizeof($validated['value']) != sizeof($products)) {
            foreach($validated['value'] as $value) {
                if(!array_key_exists($value, $products)) {
                    array_push($notFound, $value);
                }
            }
        }
        $stats = [
            'requested' => sizeof($validated['value']),
            'found'     => sizeof($products),
            'not_found' => sizeof($notFound),
        ];
        return response()->json([
            'stats' => $stats,
            'found' => $products,
            'not_found' => $notFound,
        ], 200);
    }

    /**
     * Product language stats.
     *
     * Aggregates counts of product descriptions per language.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: opencart_default
     * @response 200 [
     *   {
     *     "language_id": 1,
     *     "total": 100,
     *     "name": "English",
     *     "status": 1,
     *     "sort_order": 1
     *   }
     * ]
     */
    public function languageStats() {
        $counts = DB::connection($this->database)
            ->table('oc_product_description as d')
            ->select(
                'd.language_id',
                DB::raw('COUNT(*) as total'),
                'l.name',
                'l.status',
                'l.sort_order'
            )
            ->join('oc_language as l', 'd.language_id', '=', 'l.language_id')
            ->groupBy('d.language_id', 'l.name', 'l.status', 'l.sort_order')
            ->get();

        return response()->json($counts);
    }

    /**
     * Create product.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: opencart_default
     * @bodyParam model string required SKU model. Example: SKU-123
     * @bodyParam sku string SKU code. Example: SKU-123
     * @bodyParam quantity integer required Stock quantity. Example: 5
     * @bodyParam manufacturer_id integer required Manufacturer identifier. Example: 12
     * @bodyParam shipping boolean required Whether shipping is required. Example: true
     * @bodyParam price number required Product price. Example: 199.99
     * @bodyParam tax_class_id integer required Tax class identifier. Example: 1
     * @bodyParam date_available date required Availability date. Example: 2024-01-01
     * @bodyParam weight number required Product weight. Example: 1.25
     * @bodyParam length number required Product length. Example: 30
     * @bodyParam width number required Product width. Example: 20
     * @bodyParam height number required Product height. Example: 15
     * @bodyParam length_class_id integer required Length class identifier. Example: 1
     * @bodyParam subtract boolean required Subtract stock when ordered. Example: true
     * @bodyParam minimum integer required Minimum order quantity. Example: 1
     * @bodyParam sort_order integer required Sort order weight. Example: 0
     * @bodyParam status boolean required Product status flag. Example: true
     * @response 201 {
     *   "is_created": true,
     *   "id": 501
     * }
     * @response 409 scenario="Duplicate" {
     *   "is_created": false,
     *   "message": "item with that product_id already exists"
     * }
     */
    public function create(Request $request) {
        $validated = $request->validate([
            'model'             => 'required|string|max:64',
            'sku'               => 'string|max:64',
            'upc'               => 'string|max:12',
            'ean'               => 'string|max:14',
            'jan'               => 'string|max:13',
            'isbn'              => 'string|max:17',
            'mpn'               => 'string|max:64',
            'location'          => 'string|max:128',
            'quantity'          => 'required|integer|max:9999',
            'stock_status_id'   => 'required|integer|min:1|max:99999999999',
            'image'             => 'string|max:255',
            'manufacturer_id'   => 'required|integer|min:1|max:99999999999',
            'shipping'          => 'required|bool',
            'price'             => 'required|numeric|min:0|max:99999999999.9999',
            'points'            => 'required|integer|max:99999999',
            'tax_class_id'      => 'required|integer|min:1|max:99999999999',
            'date_available'    => 'required|date',
            'weight'            => 'required|numeric|min:0|max:999999999999999.99999999',
            'weight_class_id'   => 'required|integer|min:1|max:99999999999',
            'length'            => 'required|numeric|min:0|max:999999999999999.99999999',
            'width'             => 'required|numeric|min:0|max:999999999999999.99999999',
            'height'            => 'required|numeric|min:0|max:999999999999999.99999999',
            'length_class_id'   => 'required|integer|min:1|max:99999999999',
            'subtract'          => 'required|bool',
            'minimum'           => 'required|integer|min:1|max:99999999999',
            'sort_order'        => 'required|integer|max:99999999999',
            'status'            => 'required|bool',
        ]);

        $validated['date_added'] = Carbon::now();
        $validated['date_modified'] = Carbon::now();

        try {
            $db = DB::connection($this->database);

            $id = $db->table($this->table)
                ->insertGetId($validated);

            $db->table('oc_product_to_store')
                ->insert([
                    'product_id'    => $id,
                    'store_id'      => 0,
                ]);
            $db->table('oc_product_to_layout')
                ->insert([
                    'product_id'    => $id,
                    'store_id'      => 0,
                    'layout_id'     => 0,
                ]);

            return response()->json([
                'is_created' => true,
                'id' => $id,
            ], 201);

        } catch (QueryException $e) {
            if ($e->errorInfo[1] == 1062) {
                return response()->json([
                    'is_created' => false,
                    'message' => 'item with that product_id already exists',
                ], 409);
            }

            throw $e;
        }
    }

    /**
     * Update product.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: opencart_default
     * @urlParam id integer required Product identifier to update. Example: 123
     * @bodyParam price number Product price. Example: 189.99
     * @bodyParam quantity integer Stock quantity available. Example: 10
     * @bodyParam status boolean Product status flag. Example: true
     * @response 200 {
     *   "is_updated": true
     * }
     * @response 404 {
     *   "is_updated": false
     * }
     */
    public function update(Request $request, $id) {
        validator(['id' => $id], [
            'id' => 'required|integer|min:1|max:99999999999',
        ])->validate();
        $validated = $request->validate([
            'model'             => 'string|max:64',
            'sku'               => 'string|max:64',
            'upc'               => 'string|max:12',
            'ean'               => 'string|max:14',
            'jan'               => 'string|max:13',
            'isbn'              => 'string|max:17',
            'mpn'               => 'string|max:64',
            'location'          => 'string|max:128',
            'quantity'          => 'integer|max:9999',
            'stock_status_id'   => 'integer|min:1|max:99999999999',
            'image'             => 'string|max:255',
            'manufacturer_id'   => 'integer|min:1|max:99999999999',
            'shipping'          => 'bool',
            'price'             => 'numeric|min:0|max:99999999999.9999',
            'points'            => 'integer|max:99999999',
            'tax_class_id'      => 'integer|min:1|max:99999999999',
            'date_available'    => 'date',
            'weight'            => 'numeric|min:0|max:999999999999999.99999999',
            'weight_class_id'   => 'integer|min:1|max:99999999999',
            'length'            => 'numeric|min:0|max:999999999999999.99999999',
            'width'             => 'numeric|min:0|max:999999999999999.99999999',
            'height'            => 'numeric|min:0|max:999999999999999.99999999',
            'length_class_id'   => 'integer|min:1|max:99999999999',
            'subtract'          => 'bool',
            'minimum'           => 'integer|min:1|max:99999999999',
            'sort_order'        => 'integer|max:99999999999',
            'status'            => 'bool',
        ]);
        $validated['date_modified'] = Carbon::now();

        $affected = DB::connection($this->database)
            ->table($this->table)
            ->where('product_id', $id)
            ->update($validated);
        $status = $affected > 0 ? 200 : 404;
        return response()->json([
            'is_updated' => $affected > 0,
        ], $status);
    }

    /**
     * Delete product.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: opencart_default
     * @urlParam id integer required Product identifier to delete. Example: 123
     * @response 200 {
     *   "is_deleted": true
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
            'is_deleted' => true
        ], 200);
    }

    /**
     * Mass delete products.
     *
     * Removes products and all related records across OpenCart tables using batches of 500 IDs.
     *
     * @authenticated
     * @header database string required Target OpenCart connection, must match one of `DB_ARRAY`. Example: opencart_default
     * @bodyParam ids array required Product IDs to delete. Example: [123,456]
     * @response 200 {
     *   "message": "Products successfully deleted",
     *   "deleted_count": 2
     * }
     * @response 500 {
     *   "error": "Mass delete failed",
     *   "message": "Detailed failure reason"
     * }
     */
    public function massDeleteBatch(Request $request){
        $validated = $request->validate([
            'ids' => 'required|array'
        ]);
        // 1) ставим неограниченный PHP‑таймаут
        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }
        ini_set('max_execution_time', 0);

        $ids = array_map('intval', $validated['ids']);

        // 3) подключаемся к нужной базе
        $db = DB::connection($this->database);

        $related = [
            'oc_product_apt',
            'oc_product_attribute',
            'oc_product_color',
            'oc_product_description',
            'oc_product_discount',
            'oc_product_filter',
            'oc_product_image',
            'oc_product_option',
            'oc_product_optional',
            'oc_product_option_value',
            'oc_product_recurring',
            'oc_product_related',
            'oc_product_reward',
            'oc_product_special',
            'oc_product_to_category',
            'oc_product_to_download',
            'oc_product_to_layout',
            'oc_product_to_store',
        ];

        $chunks = array_chunk($ids, 500);

        try {
            // 4) используем транзакцию на $db
            $db->transaction(function() use ($db, $chunks, $related) {
                foreach ($chunks as $chunk) {
                    // сначала дочерние таблицы
                    foreach ($related as $table) {
                        $db->table($table)
                        ->whereIn('product_id', $chunk)
                        ->delete();
                    }
                    // потом главная
                    $db->table('oc_product')
                    ->whereIn('product_id', $chunk)
                    ->delete();
                }
                $urlQueries = [];
                foreach($chunk as $id)
                    $urlQueries[] = "product_id={$id}";
                $db->table('oc_url_alias')
                    ->whereIn('query', $urlQueries)
                    ->delete();
            });

            return response()->json([
                'message'       => 'Products successfully deleted',
                'deleted_count' => count($ids),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Mass delete failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function changePrice($id, Request $request) {
        validator(['id' => $id], [
            'id' => 'required|integer|min:1|max:99999999999',
        ])->validate();
        $validated = $request->validate([
            'price' => 'numeric|min:0|max:99999999999.9999',
        ]);
        $price = $request->price;
        $dateModified = Carbon::now();
        $uvpPrice = $this->processUvp($price);

        $db = DB::connection($this->database);

        $affectedMain = $db
            ->table($this->table)
            ->where('product_id', $id)
            ->update([
                'price'         => $uvpPrice,
                'date_modified' => $dateModified,
            ]);
        
        $special = $db
            ->table('oc_product_special')
            ->where('product_id', $id)
            ->first();

        if($special) {
            $db
                ->table('oc_product_special')
                ->where('product_id', $id)
                ->update([
                    'price' => $price,
                ]);
        } else {
            $db
                ->table('oc_product_special')
                ->create([
                    'product_id' => $id,
                    'customer_group_id' => 1,
                    'priority' => 0,
                    'price' => $price
                ]);
        }

        $status = $affectedMain > 0 ? 200 : 404;
        return response()->json([
            'is_updated_main' => $affectedMain > 0,
            'uvp_price' => $uvpPrice,
        ], $status);
    }
}
