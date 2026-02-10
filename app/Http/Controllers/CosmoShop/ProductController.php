<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \Carbon\Carbon;

use App\Http\Traits\CosmoShop\MappingTrait;

use Illuminate\Support\Facades\DB;
/**
 * @group CosmoShop
 * @subgroup Product
 */
class ProductController extends Controller
{
    use MappingTrait;
    public $tableName = \TableName::Product->value;
    public $database;
    public $pk = 'artikelid';
    public $mapping = [
        'article_id' => 'artikelid',
        'article_base_id' => 'artikelbaseid',
        'article_number' => 'artikelnr',
        'is_auto' => 'auto',
        'is_inactive' => 'inaktiv',
        'type' => 'type',
        'created_at' => 'erfasst',
        'updated_at' => 'geaendert',
        'updated_by' => 'user',
        'tax_id' => 'mwstid',
        'discount_group_id' => 'rabattgruppeid',
        'delivery_time_id' => 'lieferzeitid',
        'unit_id' => 'einheitid',
        'content' => 'inhalt',
        'base_unit' => 'grundeinheit',
        'package_unit' => 'vpe',
        'weight_net' => 'gewicht_netto',
        'weight_gross' => 'gewicht_brutto',
        'is_special_price' => 'special_price',
        'is_new' => 'neu',
        'is_price_request' => 'preiswunsch',
        'is_ebay' => 'ebay',
        'kelkoo_category' => 'kelkoo_category',
        'manufacturer' => 'hersteller',
        'ean' => 'ean',
        'request_count' => 'anfrage',
        'is_downloadable' => 'download',
        'url_key' => 'urlkey',
        'suggested_price' => 'uvp',
        'condition' => 'zustand',
        'bulk_pricing_mode' => 'staffelhandling_ausf',
        'stock_threshold_1' => 'bestand_schwelle_1',
        'stock_lead_time_1' => 'bestand_lz_1',
        'stock_threshold_2' => 'bestand_schwelle_2',
        'stock_lead_time_2' => 'bestand_lz_2',
        'is_stock_disabled' => 'bestand_deaktiv',
        'max_order_quantity' => 'max_bestellmenge',
        'stock_display_mode' => 'bestand_anzeigen',
        'is_stock_hidden' => 'bestand_ausblenden',
        'ignore_all_variants_stock' => 'bestand_ignore_all_variants',
        'product_group_id' => 'produktgruppeid',
        'google_category' => 'google_category',
        'is_live_shopping_active' => 'liveshopping_aktiv',
        'live_shopping_start' => 'liveshopping_anfang',
        'live_shopping_end' => 'liveshopping_ende',
        'live_shopping_discount' => 'liveshopping_rabatt',
        'exclude_from_portal_export' => 'no_portal_export',
        'disable_amazon_payment' => 'no_amazon_payment',
        'has_fixed_bundle_price' => 'fixed_bundle_price',
        'subsequent_article' => 'subsequent',
        'requires_proof' => 'nachweispflicht',
        'time_planner_start' => 'timeplaner_start',
        'time_planner_end' => 'timeplaner_ende',
        'min_order_quantity' => 'min_bestellmenge',
        'is_bundle_configurable' => 'bundle_configarticle',
        'second_price_multiplier' => 'second_price_multiplier',
        'is_configurable_article' => 'is_configarticle',
        'is_configurable_component' => 'is_configarticle_component',
        'has_flexible_shipping' => 'flexible_shipping',
        'is_sample_allowed' => 'sample_allowed',
        'is_search_indexed' => 'indexed_for_search',
        'has_fixed_bulk_pricing' => 'staffel_fixed',
        'jf_sku' => 'jfsku',
        'sync_with_jtl' => 'jtl_product_sync',
        'jtl_length' => 'jtl_dimensions_length',
        'jtl_width' => 'jtl_dimensions_width',
        'jtl_height' => 'jtl_dimensions_height',
        'custom_data' => 'custom',
    ];
    public $flippedMapping;
    public $selectArr = [];

    public function __construct(Request $request)
    {
        $array = env('DB_ARRAY');
        $availableDatabases = json_decode($array);
        $this->database = $request->header('database');
        if (!$this->database || !in_array($this->database, $availableDatabases)) {
            abort(400, "Database header is required. Expected one of the database values: " . implode(', ', $availableDatabases));
        }
        $this->flippedMapping = array_flip($this->mapping);
        foreach ($this->mapping as $key => $value) {
            array_push($this->selectArr, "{$value} as {$key}");
        }
        if (!str_contains($this->database, 'jv'))
            abort(400, 'Your current database is XL. You cant use this endpoint here');
    }
    function changeFields($collection)
    {
        $mappedProduct = [];
        foreach ($this->flippedMapping as $dbField => $apiField) {
            if (isset($collection->$dbField))
                $mappedProduct[$apiField] = $collection->$dbField;
        }
        return $mappedProduct;
    }

    /**
     * Resolve product ids by mapped field.
     *
     * Pass a short field alias (for example `ean` or `article_number`) together with the values you need,
     * and the endpoint returns matching article ids plus statistics for misses.
     *
     * @authenticated
     * @header database string required Target CosmoShop connection, must match one of `DB_ARRAY`. Example: jv.de
     * @bodyParam key string required Field alias from the mapping table (e.g. `ean`, `article_number`). Example: ean
     * @bodyParam value array required Values to resolve against the selected key. Example: ["4000001000017","4000001000024"]
     * @response 200 {
     *   "stats": {
     *     "requested": 2,
     *     "found": 1,
     *     "not_found": 1
     *   },
     *   "found": {
     *     "4000001000017": 12345
     *   },
     *   "not_found": [
     *     "4000001000024"
     *   ]
     * }
     */
    public function getId(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string',
            'value' => 'required|array'
        ]);
        $validated['value'] = array_unique($validated['value']);
        $key = $this->mapping[$validated['key']];
        $products = DB::connection($this->database)->table($this->tableName)->whereIn($key, $validated['value'])->pluck($this->pk, $key)->toArray();

        if (!$products) {
            return response()->json([
                'message' => 'Item was not found'
            ], 404);
        }
        $notFound = [];
        if (sizeof($validated['value']) != sizeof($products)) {
            foreach ($validated['value'] as $value) {
                if (!array_key_exists($value, $products)) {
                    array_push($notFound, $value);
                }
            }
        }
        $stats = [
            'requested' => sizeof($validated['value']),
            'found' => sizeof($products),
            'not_found' => sizeof($notFound),
        ];
        return response()->json([
            'stats' => $stats,
            'found' => $products,
            'not_found' => $notFound,
        ], 200);
    }

    /**
     * Create a new product record.
     *
     * Validates the payload, enforces unique article numbers/EANs and writes the product to the CosmoShop tables.
     *
     * @authenticated
     * @header database string required Target CosmoShop connection, must match one of `DB_ARRAY`. Example: jv.de
     * @bodyParam manufacturer string required Producer name stored in `hersteller`. Example: XL Mebel
     * @bodyParam ean string required Unique product EAN (max 13 chars). Example: 4000001000017
     * @bodyParam base_unit integer required Base unit identifier (`grundeinheit`). Example: 1
     * @bodyParam live_shopping_discount number required Discount value for live shopping (`liveshopping_rabatt`). Example: 10.5
     * @bodyParam has_fixed_bundle_price boolean required Whether bundle price is fixed. Example: true
     * @bodyParam is_bundle_configurable boolean required Marks article as configurable bundle. Example: false
     * @bodyParam article_number string Unique article number (optional, generated elsewhere). Example: XR-1001
     * @bodyParam content number Quantity or capacity (`inhalt`). Example: 2.5
     * @bodyParam suggested_price number UVP value (`uvp`). Example: 349.00
     * @bodyParam custom_data string JSON/text stored in `custom`. Example: {"color":"oak"}
     * @response 201 {
     *   "message": "Item was created",
     *   "data": {
     *     "article_id": 12345,
     *     "article_number": "XR-1001",
     *     "ean": "4000001000017"
     *   }
     * }
     * @response 404 {
     *   "message": "Item with that EAN already exists. You need to create a product with unique EAN"
     * }
     * @response 500 {
     *   "message": "Failed to create item"
     * }
     */
    public function create(Request $request)
    {
        $db = DB::connection($this->database);
        $validated = $request->validate([
            'article_base_id' => 'integer',
            'article_number' => 'string|nullable',
            'is_auto' => 'integer',
            'is_inactive' => 'integer',
            'type' => 'integer',
            'created_at' => 'date',
            'updated_at' => 'date',
            'updated_by' => 'string|nullable',
            'tax_id' => 'integer',
            'discount_group_id' => 'integer',
            'delivery_time_id' => 'integer',
            'unit_id' => 'integer',
            'content' => 'decimal:0,10',
            'base_unit' => 'required|integer',
            'package_unit' => 'integer|nullable',
            'weight_net' => 'decimal:0,3',
            'weight_gross' => 'decimal:0,3',
            'is_special_price' => 'bool',
            'is_new' => 'bool',
            'is_price_request' => 'bool',
            'is_ebay' => 'bool',
            'kelkoo_category' => 'string',
            'manufacturer' => 'required|string',
            'ean' => 'required|string|max:13',
            'request_count' => 'integer',
            'is_downloadable' => 'bool',
            'url_key' => 'string',
            'suggested_price' => 'decimal:0,10|nullable',
            'condition' => 'integer',
            'bulk_pricing_mode' => 'string|in:d,je_ausf,je_artikel',
            'stock_threshold_1' => 'integer',
            'stock_lead_time_1' => 'integer',
            'stock_threshold_2' => 'integer',
            'stock_lead_time_2' => 'integer',
            'is_stock_disabled' => 'bool',
            'max_order_quantity' => 'integer',
            'stock_display_mode' => 'string|in:default,nein,nur_detail,detail_und_vorschau',
            'is_stock_hidden' => 'bool',
            'ignore_all_variants_stock' => 'bool',
            'product_group_id' => 'integer',
            'google_category' => 'string',
            'is_live_shopping_active' => 'integer',
            'live_shopping_start' => 'date',
            'live_shopping_end' => 'date',
            'live_shopping_discount' => 'required|decimal:0,2',
            'exclude_from_portal_export' => 'integer',
            'disable_amazon_payment' => 'bool',
            'has_fixed_bundle_price' => 'required|bool',
            'subsequent_article' => 'integer|nullable',
            'requires_proof' => 'bool',
            'time_planner_start' => 'date',
            'time_planner_end' => 'date',
            'min_order_quantity' => 'integer',
            'is_bundle_configurable' => 'required|bool',
            'second_price_multiplier' => 'decimal:0,2|nullable',
            'is_configurable_article' => 'bool',
            'is_configurable_component' => 'bool',
            'has_flexible_shipping' => 'bool',
            'is_sample_allowed' => 'bool',
            'is_search_indexed' => 'bool',
            'has_fixed_bulk_pricing' => 'bool',
            'jf_sku' => 'string|nullable',
            'sync_with_jtl' => 'bool',
            'jtl_length' => 'decimal:0,3',
            'jtl_width' => 'decimal:0,3',
            'jtl_height' => 'decimal:0,3',
            'custom_data' => 'string',
        ]);
        $validated['created_at'] = $validated['created_at'] ?? now();
        $validated['updated_at'] = $validated['updated_at'] ?? now();
        $validated['updated_by'] = $validated['updated_by'] ?? 'API';
        $validated['tax_id'] = $validated['tax_id'] ?? 3;
        $validated['package_unit'] = $validated['package_unit'] ?? 1;
        $validated['suggested_price'] = $validated['suggested_price'] ?? 0.0;


        $mappedData = [];
        $isArticleNumberAlreadyExists = DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->mapping['article_number'], $validated['article_number'])
            ->exists();
        if ($isArticleNumberAlreadyExists) {
            $db->rollBack();
            return response()->json(['message' => 'Item with that article number already exists. You need to create a unique article number'], 404);
        }

        $isEanAlreadyExists = DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->mapping['ean'], $validated['ean'])
            ->exists();
        if ($isEanAlreadyExists) {
            $db->rollBack();
            return response()->json(['message' => 'Item with that EAN already exists. You need to create a product with unique EAN'], 404);
        }
        foreach ($validated as $key => $value) {
            if (isset($this->mapping[$key])) {
                $mappedData[$this->mapping[$key]] = $value;
            }
        }
        $db->beginTransaction();
        $productId = $db
            ->table($this->tableName)
            ->insertGetId($mappedData);
        $product = $db
            ->table($this->tableName)
            ->where('artikelid', $productId)
            ->first();

        if (!$product) {
            $db->rollBack();
            return response()->json(['message' => 'Failed to create item'], 500);
        }
        $db->commit();
        return response()->json([
            'message' => 'Item was created',
            'data' => $this->changeFields($product)
        ], 201);
    }

    /**
     * Retrieve mapped values for a related entity.
     *
     * Supports dynamic output for product/category/delivery content via the mapping definitions.
     *
     * @authenticated
     * @header database string required Target CosmoShop connection, must match one of `DB_ARRAY`. Example: jv.de
     * @bodyParam key string required Product field alias to match (e.g. `ean`, `article_number`). Example: ean
     * @bodyParam value array required Values for the provided key. Example: ["4000001000017"]
     * @bodyParam output_field string required Target field alias (supports prefixes `category_content.`, `product_content.`, `delivery_content.`). Example: product_content.name
     * @response 200 {
     *   "stats": {
     *     "requested": 1,
     *     "found": 1,
     *     "not_found": 0
     *   },
     *   "found": {
     *     "4000001000017": "Oak Dining Table"
     *   },
     *   "not_found": []
     * }
     * @response 400 {
     *   "message": "output field not found",
     *   "available_fields": [
     *     "name",
     *     "description"
     *   ]
     * }
     */
    public function getCustomOutput(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required',
            'value' => 'required|array',
            'output_field' => 'required'
        ]);
        $db = DB::connection($this->database)->table('shopartikel as product');
        $key = "product.{$this->mapping[$validated['key']]}";
        $value = array_unique($validated['value']);
        if(str_starts_with($validated['output_field'], 'category_content.')) {
            $customMapping = $this->getMapping('category_content');
            $db = $db
                ->join('shoprubrikartikel as mtm', 'product.artikelid', '=', 'mtm.artikelid')
                ->join('shoprubrikencontent as cat', 'mtm.rubnum', '=', 'cat.rubnumref');
            $prefix = 'category_content.';
            $outputField = preg_replace('/^' . preg_quote($prefix, '/') . '/', '', $validated['output_field']);
            if(!isset($customMapping[$outputField])) {
                return response()->json([
                    'message' => 'output field not found',
                    'available_fields' => array_keys($customMapping)
                ]);
            }
            $outputFieldMapped = 'cat.'.$customMapping[$outputField];
        }
        elseif(str_starts_with($validated['output_field'], 'product_content.')) {
            $customMapping = $this->getMapping('product_content');
            $db = $db
                ->join('shopartikelcontent as content', 'product.artikelid', '=', 'content.artikelid');
            $prefix = 'product_content.';
            $outputField = preg_replace('/^' . preg_quote($prefix, '/') . '/', '', $validated['output_field']);
            if(!isset($customMapping[$outputField])) {
                return response()->json([
                    'message' => 'output field not found',
                    'available_fields' => array_keys($customMapping)
                ]);
            }
            $outputFieldMapped = 'content.'.$customMapping[$outputField];
        }
        elseif(str_starts_with($validated['output_field'], 'delivery_content.')) {
            $customMapping = $this->getMapping('delivery_content');
            $db = $db
                ->join('shoplieferzeitencontent as content', 'product.lieferzeitid', '=', 'content.lz_id');
            $prefix = 'delivery_content.';
            $outputField = preg_replace('/^' . preg_quote($prefix, '/') . '/', '', $validated['output_field']);
            if(!isset($customMapping[$outputField])) {
                return response()->json([
                    'message' => 'output field not found',
                    'available_fields' => array_keys($customMapping)
                ]);
            }
            $outputFieldMapped = 'content.'.$customMapping[$outputField];
        }
        else
            $outputFieldMapped = $this->mapping[$validated['output_field']];

        $items = $db->whereIn($key, $validated['value'])
            ->pluck($outputFieldMapped, $key)
            ->toArray();

        if (!$items) {
            $stats = [
                'requested' => sizeof($validated['value']),
                'found' => 0,
                'not_found' => sizeof($value),
            ];
            return response()->json([
                'stats' => $stats,
                'found' => [],
                'not_found' => $value,
            ], 200);
        }

        $notFound = [];
        if (sizeof($validated['value']) != sizeof($items)) {
            foreach ($validated['value'] as $value) {
                if (!array_key_exists($value, $items)) {
                    array_push($notFound, $value);
                }
            }
        }
        $stats = [
            'requested' => sizeof($validated['value']),
            'found' => sizeof($items),
            'not_found' => sizeof($notFound),
        ];
        return response()->json([
            'stats' => $stats,
            'found' => $items,
            'not_found' => $notFound,
        ], 200);
    }

    /**
     * Fetch the full product payload.
     *
     * Returns the product with joined attributes, prices, content, stock and related collections.
     *
     * @authenticated
     * @header database string required Target CosmoShop connection, must match one of `DB_ARRAY`. Example: jv.de
     * @urlParam id integer required Product identifier (artikelid) to inspect. Example: 12345
     * @response 200 {
     *   "article_base_id": 0,
     *   "article_number": "XR-1001",
     *   "ean": "4000001000017",
     *   "attributes": [
     *     {
     *       "article_base_id": 0,
     *       "attribute_id": 12
     *     }
     *   ],
     *   "prices": [
     *     {
     *       "price_tier": 1,
     *       "price": "299.00"
     *     }
     *   ]
     * }
     * @response 404 {
     *   "message": "Item not found"
     * }
     */
    public function fullProduct(int $id, Request $request)
    {
        $sql = <<<'SQL'
    SELECT
        a.artikelbaseid AS `article_base_id`,
        a.artikelnr AS `article_number`,
        a.auto AS `is_auto`,
        a.inaktiv AS `is_inactive`,
        a.type AS `type`,
        a.erfasst AS `created_at`,
        a.geaendert AS `updated_at`,
        a.user AS `updated_by`,
        a.mwstid AS `tax_id`,
        a.rabattgruppeid AS `discount_group_id`,
        a.lieferzeitid AS `delivery_time_id`,
        a.einheitid AS `unit_id`,
        a.inhalt AS `content`,
        a.grundeinheit AS `base_unit`,
        a.vpe AS `package_unit`,
        a.gewicht_netto AS `weight_net`,
        a.gewicht_brutto AS `weight_gross`,
        a.special_price AS `is_special_price`,
        a.neu AS `is_new`,
        a.preiswunsch AS `is_price_request`,
        a.ebay AS `is_ebay`,
        a.kelkoo_category AS `kelkoo_category`,
        a.hersteller AS `manufacturer`,
        a.ean AS `ean`,
        a.anfrage AS `request_count`,
        a.download AS `is_downloadable`,
        a.urlkey AS `url_key`,
        a.uvp AS `suggested_price`,
        a.zustand AS `condition`,
        a.staffelhandling_ausf AS `bulk_pricing_mode`,
        a.bestand_schwelle_1 AS `stock_threshold_1`,
        a.bestand_lz_1 AS `stock_lead_time_1`,
        a.bestand_schwelle_2 AS `stock_threshold_2`,
        a.bestand_lz_2 AS `stock_lead_time_2`,
        a.bestand_deaktiv AS `is_stock_disabled`,
        a.max_bestellmenge AS `max_order_quantity`,
        a.bestand_anzeigen AS `stock_display_mode`,
        a.bestand_ausblenden AS `is_stock_hidden`,
        a.bestand_ignore_all_variants AS `ignore_all_variants_stock`,
        a.produktgruppeid AS `product_group_id`,
        a.google_category AS `google_category`,
        a.liveshopping_aktiv AS `is_live_shopping_active`,
        a.liveshopping_anfang AS `live_shopping_start`,
        a.liveshopping_ende AS `live_shopping_end`,
        a.liveshopping_rabatt AS `live_shopping_discount`,
        a.no_portal_export AS `exclude_from_portal_export`,
        a.no_amazon_payment AS `disable_amazon_payment`,
        a.fixed_bundle_price AS `has_fixed_bundle_price`,
        a.subsequent AS `subsequent_article`,
        a.nachweispflicht AS `requires_proof`,
        a.timeplaner_start AS `time_planner_start`,
        a.timeplaner_ende AS `time_planner_end`,
        a.min_bestellmenge AS `min_order_quantity`,
        a.bundle_configarticle AS `is_bundle_configurable`,
        a.second_price_multiplier AS `second_price_multiplier`,
        a.is_configarticle AS `is_configurable_article`,
        a.is_configarticle_component AS `is_configurable_component`,
        a.flexible_shipping AS `has_flexible_shipping`,
        a.sample_allowed AS `is_sample_allowed`,
        a.indexed_for_search AS `is_search_indexed`,
        a.staffel_fixed AS `has_fixed_bulk_pricing`,
        a.jfsku AS `jf_sku`,
        a.jtl_product_sync AS `sync_with_jtl`,
        a.jtl_dimensions_length AS `jtl_length`,
        a.jtl_dimensions_width AS `jtl_width`,
        a.jtl_dimensions_height AS `jtl_height`,
        a.custom AS `custom_data`,
        (
            SELECT JSON_ARRAYAGG(JSON_OBJECT(
                'article_base_id', prodattr.artikelbaseid,
                'attribute_id', prodattr.attributid,
                'updated_at', prodattr.changed,
                'attribute', (
                    SELECT JSON_ARRAYAGG(JSON_OBJECT(
                        'id', attr.id,
                        'level', attr.level,
                        'label', attr.label,
                        'sort', attr.sort,
                        'refid', attr.refid,
                        'google_category', attr.google_category,
                        'root_display_group', attr.root_display_group,
                        'display_type', attr.display_type
                    ))
                    FROM shopattribute AS attr
                    WHERE attr.id = prodattr.attributid
                )
            ))
            FROM shopartikel_attribute AS prodattr
            WHERE prodattr.artikelid = a.artikelid
        ) AS attributes,
        (
            SELECT JSON_ARRAYAGG(JSON_OBJECT(
                'price_tier', price.staffel,
                'price', price.preis,
                'percentage', price.prozent,
                'price_basis', price.basis,
                'filter', price.filter,
                'currency', price.waehrung,
                'auto_calculate', price.auto,
                'tier_id', price.staffelid,
                'alternative_price', price.second_price
            ))
            FROM shopartikelpreise AS price
            WHERE price.artikelid = a.artikelid
        ) AS prices,
        (
            SELECT JSON_ARRAYAGG(JSON_OBJECT(
                'language', productcontent.sprache,
                'title', productcontent.name,
                'description', productcontent.bezeichnung,
                'description_html', productcontent.bezeichnung_html,
                'short_description', productcontent.bezeichnung_kurz,
                'is_plain_description', productcontent.bezeichnung_plain,
                'keywords', productcontent.keywords,
                'image_alt_text', productcontent.bilder_alt,
                'search_field', productcontent.suchfeld,
                'live_shopping_text', productcontent.liveshopping_text,
                'url_key', productcontent.urlkey,
                'second_price_label', productcontent.second_price_label,
                'features', productcontent.features
            ))
            FROM shopartikelcontent AS productcontent
            WHERE productcontent.artikelid = a.artikelid
        ) AS content,
        (
            SELECT JSON_ARRAYAGG(JSON_OBJECT(
                'stock', stock.bestand,
                'min_stock', stock.bestand_min,
                'ignore_stock', stock.bestand_ignore,
                'created_at', stock.timestamp,
                'storage_location', stock.storage
            ))
            FROM shopartikelbestaende AS stock
            WHERE stock.artikelid = a.artikelid
        ) AS stock,
        (
            SELECT JSON_ARRAYAGG(JSON_OBJECT(
                'execution_id', v.artikelausfuehrungid,
                'execution_number', v.ausfuehrungnr,
                'order', v.`order`,
                'custom_key', v.custom_key,
                'attribute_class_id', v.attributsklasseid,
                'attribute_class_lock', v.attributsklasse_lock,
                'content', (
                    SELECT JSON_ARRAYAGG(JSON_OBJECT(
                        'language', vc.sprache,
                        'name', vc.bezeichnung,
                        'name_wk', vc.bezeichnungwk,
                        'description', vc.beschreibung
                    ))
                    FROM shopartikelausfuehrungcontent AS vc
                    WHERE vc.artikelausfuehrungid = v.artikelausfuehrungid
                )
            ))
            FROM shopartikelausfuehrungen AS v
            WHERE v.artikelid = a.artikelid
        ) AS variations,
        (
            SELECT JSON_ARRAYAGG(JSON_OBJECT(
                'sort_order', manytomanycat.ordnum,
                'priority_level', manytomanycat.priority,
                'category_id', cat.rubid,
                'category_code', cat.rubnum,
                'category_image', cat.rubbild,
                'sort_type', cat.rubsort,
                'mode', cat.rubmode,
                'sort_order', cat.ruborder,
                'customer_groups', cat.rub_kdgruppen,
                'payment_selection', cat.rub_bpsel,
                'parent_path', cat.rub_parent,
                'url_key', cat.ruburlkey,
                'parent_id', cat.parentid,
                'updated_at', cat.geaendert,
                'article_sort_string', cat.rubartikel_sort_str,
                'disable_amazon_payment', cat.no_amazon_payment,
                'disable_indexing', cat.noindex,
                'external_key', cat.key_extern,
                'disable_additional_popup', cat.rub_zusatz_no_popup,
                'show_additional_bottom', cat.rub_zusatz_bottom,
                'disable_article_listing', cat.disable_articlelisting,
                'content', (
                SELECT JSON_ARRAYAGG(JSON_OBJECT(
                    'language', catcontent.rubsprache,
                    'category_name', catcontent.rubnam,
                    'description', catcontent.rubtext,
                    'meta_keywords', catcontent.keywords,
                    'short_description', catcontent.rubtext_kurz,
                    'url_key', catcontent.urlkey,
                    'slider_id', catcontent.slider_id
                ))
                FROM shoprubrikencontent AS catcontent
                WHERE catcontent.rubid = manytomanycat.rubid
                )
            ))
            FROM shoprubrikartikel AS manytomanycat
            INNER JOIN shoprubriken AS cat
            ON cat.rubid = manytomanycat.rubid
            WHERE manytomanycat.artikelid = a.artikelid
        ) AS categories,
        (
            SELECT JSON_ARRAYAGG(JSON_OBJECT(
                'supplier_info_id', sup.lieferanteninfoid,
                'supplier_id', sup.lieferantid,
                'supplier_article_number', sup.liefernr,
                'purchase_price', sup.preis_ek,
                'sold_quantity', sup.abverkauf,
                'delivery_time', sup.lieferzeit,
                'delivery_date', sup.lieferdatum
            ))
            FROM shopartikellieferanteninfo AS sup
            WHERE sup.artikelid = a.artikelid
        ) AS supplier_info,
        (
            SELECT JSON_ARRAYAGG(JSON_OBJECT(
                'property_value', property.propertyvalue,
                'property_id', prop.id,
                'property_name', prop.propertyname,
                'property_code', prop.propertycode,
                'is_visible_frontend', prop.frontendavailable,
                'is_visible_backend', prop.backendavailable,
                'plugin_name', prop.plugin,
                'sort_order', prop.sort
            ))
            FROM shopartikelproperties AS property
            JOIN shopproperties AS prop
            ON prop.id = property.propertyid
            WHERE property.artikelid = a.artikelid
        ) AS properties,
        (
            SELECT JSON_ARRAYAGG(JSON_OBJECT(
                'id', media.id,
                'key', media.key,
                'type', media.art,
                'media_directory', media.typ,
                'filename', media.dateiname,
                'extension', media.endung,
                'sort_order', media.`order`,
                'width', media.breite,
                'height', media.hoehe,
                'variant_assignment', media.zuordnung,
                'version', media.version,
                'updated_at', media.timestamp
            ))
            FROM shopmedia AS media
            WHERE media.key = a.artikelnr
        ) AS media
    FROM shopartikel AS a
    WHERE a.artikelid = :id
    SQL;


        $raw = DB::connection($this->database)->selectOne($sql, ['id' => $id]);
        if (!$raw) {
            abort(404, 'Item not found');
        }
        $rawArray = (array) $raw;

        $product = array_diff_key($rawArray, array_flip([
            'attributes',
            'prices',
            'content',
            'stock',
            'variations',
            'categories',
            'supplier_info',
            'properties',
            'media'
        ]));

        $data = [
            'product' => $product,
            'attributes' => json_decode($raw->attributes, true) ?: [],
            'prices' => json_decode($raw->prices, true) ?: [],
            'content' => json_decode($raw->content, true) ?: [],
            'stock' => json_decode($raw->stock, true) ?: [],
            'variations' => json_decode($raw->variations, true) ?: [],
            'categories' => json_decode($raw->categories, true) ?: [],
            'supplier_info' => json_decode($raw->supplier_info, true) ?: [],
            'properties' => json_decode($raw->properties, true) ?: [],
            'media' => json_decode($raw->media, true) ?: [],
        ];

        return response()->json(['item' => $data], 200);

    }

    /**
     * Update an existing product.
     *
     * Applies the provided payload to the product row and returns both old and new snapshots.
     *
     * @authenticated
     * @header database string required Target CosmoShop connection, must match one of `DB_ARRAY`. Example: jv.de
     * @urlParam id integer required Product identifier (artikelid) to update. Example: 12345
     * @bodyParam manufacturer string Manufacturer name stored in `hersteller`. Example: XL Mebel
     * @bodyParam ean string required Unique EAN, must remain unique. Example: 4000001000017
     * @bodyParam suggested_price number UVP value (`uvp`). Example: 399.00
     * @bodyParam custom_data string JSON/text stored in `custom`. Example: {"color":"walnut"}
     * @response 200 {
     *   "message": "updated",
     *   "old_item": {
     *     "ean": "4000001000017"
     *   },
     *   "new_item": {
     *     "ean": "4000001000017",
     *     "manufacturer": "XL Mebel"
     *   }
     * }
     * @response 404 {
     *   "message": "Item not found"
     * }
     */
    public function update(int $id, Request $request)
    {
        $db = DB::connection($this->database);
        $validated = $request->validate([
            'article_base_id' => 'integer',
            'article_number' => 'string|nullable',
            'is_auto' => 'integer',
            'is_inactive' => 'integer',
            'type' => 'integer',
            'created_at' => 'date',
            'updated_at' => 'date',
            'updated_by' => 'string|nullable',
            'tax_id' => 'integer',
            'discount_group_id' => 'integer',
            'delivery_time_id' => 'integer',
            'unit_id' => 'integer',
            'content' => 'decimal:0,10',
            'base_unit' => 'integer',
            'package_unit' => 'integer|nullable',
            'weight_net' => 'decimal:0,3',
            'weight_gross' => 'decimal:0,3',
            'is_special_price' => 'bool',
            'is_new' => 'bool',
            'is_price_request' => 'bool',
            'is_ebay' => 'bool',
            'kelkoo_category' => 'string',
            'manufacturer' => 'string',
            'ean' => 'string',
            'request_count' => 'integer',
            'is_downloadable' => 'bool',
            'url_key' => 'string',
            'suggested_price' => 'decimal:0,10|nullable',
            'condition' => 'integer',
            'bulk_pricing_mode' => 'string|in:d,je_ausf,je_artikel',
            'stock_threshold_1' => 'integer',
            'stock_lead_time_1' => 'integer',
            'stock_threshold_2' => 'integer',
            'stock_lead_time_2' => 'integer',
            'is_stock_disabled' => 'bool',
            'max_order_quantity' => 'integer',
            'stock_display_mode' => 'string|in:default,nein,nur_detail,detail_und_vorschau',
            'is_stock_hidden' => 'bool',
            'ignore_all_variants_stock' => 'bool',
            'product_group_id' => 'integer',
            'google_category' => 'string',
            'is_live_shopping_active' => 'integer',
            'live_shopping_start' => 'date',
            'live_shopping_end' => 'date',
            'live_shopping_discount' => 'decimal:0,2',
            'exclude_from_portal_export' => 'integer',
            'disable_amazon_payment' => 'bool',
            'has_fixed_bundle_price' => 'bool',
            'subsequent_article' => 'integer|nullable',
            'requires_proof' => 'bool',
            'time_planner_start' => 'date',
            'time_planner_end' => 'date',
            'min_order_quantity' => 'integer',
            'is_bundle_configurable' => 'bool',
            'second_price_multiplier' => 'decimal:0,2|nullable',
            'is_configurable_article' => 'bool',
            'is_configurable_component' => 'bool',
            'has_flexible_shipping' => 'bool',
            'is_sample_allowed' => 'bool',
            'is_search_indexed' => 'bool',
            'has_fixed_bulk_pricing' => 'bool',
            'jf_sku' => 'string|nullable',
            'sync_with_jtl' => 'bool',
            'jtl_length' => 'decimal:0,3',
            'jtl_width' => 'decimal:0,3',
            'jtl_height' => 'decimal:0,3',
            'custom_data' => 'string',
        ]);
        $validated['updated_at'] = now();

        $isEanAlreadyExists = DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->mapping['ean'], $validated['ean'])
            ->exists();
        if ($isEanAlreadyExists) {
            $db->rollBack();
            return response()->json(['message' => 'Item with that EAN already exists. You need to create a product with unique EAN'], 404);
        }

        $mappedData = [];
        foreach ($validated as $key => $value) {
            if (isset($this->mapping[$key]))
                $mappedData[$this->mapping[$key]] = $value;
        }
        $oldItem = $db
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->first(array_keys($mappedData));
        $affected = $db
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->update($mappedData);
        if ($affected == 0)
            return response()->json(['message' => 'Item not found'], 404);
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

    /**
     * Paginate CosmoShop products.
     *
     * Provides a lightweight listing with optional filters for active items and attribute inclusion.
     *
     * @authenticated
     * @header database string required Target CosmoShop connection, must match one of `DB_ARRAY`. Example: jv.de
     * @queryParam per_page integer Results per page, max 250, default 100. Example: 150
     * @queryParam is_active boolean Filter by active state (`true` keeps active products). Example: true
     * @queryParam include_attributes boolean When omitted, only base products (artikelbaseid = 0) are returned. Example: true
     * @response 200 {
     *   "data": [
     *     {
     *       "article_id": 12345,
     *       "ean": "4000001000017"
     *     }
     *   ],
     *   "pagination": {
     *     "current_page": 1,
     *     "last_page": 10,
     *     "per_page": 100,
     *     "total": 1000
     *   }
     * }
     */
    public function paginate(Request $request)
    {
        $perPage = 100;
        if($request->has('per_page'))
            if($request->per_page > 250)
                $perPage = 250;
            else
                $perPage = $request->per_page;

        $sql = DB::connection($this->database)
            ->table($this->tableName);

        if($request->has('is_active'))
            $products = $sql->where('inaktiv', !$request->is_active);

        if(!$request->has('include_attributes'))
            $products = $sql->where('artikelbaseid', '=', 0);

        $products = $sql->paginate($perPage);


        $mappedItems = $products->map(function ($item) {
            return $this->changeFields($item);
        });

        return response()->json([
            'data' => $mappedItems,
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ], 200);
    }

    /**
     * Paginate products updated by a specific user.
     *
     * Filters the listing by the `user` column and returns the paginated payload.
     *
     * @authenticated
     * @header database string required Target CosmoShop connection, must match one of `DB_ARRAY`. Example: jv.de
     * @queryParam updated_by string required User login/identifier stored in the `user` column. Example: api@xl-mebel.de
     * @response 200 {
     *   "data": [
     *     {
     *       "article_id": 12345,
     *       "updated_by": "api@xl-mebel.de"
     *     }
     *   ],
     *   "pagination": {
     *     "current_page": 1,
     *     "last_page": 3,
     *     "per_page": 100,
     *     "total": 250
     *   }
     * }
     */
    public function paginateAllByUser(Request $request)
    {
        $validated = $request->validate([
            'updated_by' => 'required|string'
        ]);
        $products = DB::connection($this->database)
            ->table($this->tableName)
            ->where('user', $validated['updated_by'])
            ->paginate(100);

        $mappedItems = $products->map(function ($item) {
            return $this->changeFields($item);
        });

        return response()->json([
            'data' => $mappedItems,
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ], 200);
    }

    /**
     * Paginate product ids only.
     *
     * Returns article ids ordered by EAN for lightweight batch processing.
     *
     * @authenticated
     * @header database string required Target CosmoShop connection, must match one of `DB_ARRAY`. Example: jv.de
     * @response 200 {
     *   "data": [
     *     12345,
     *     12346
     *   ],
     *   "pagination": {
     *     "current_page": 1,
     *     "last_page": 5,
     *     "per_page": 1000,
     *     "total": 5000
     *   }
     * }
     */
    public function paginateIds(Request $request)
    {
        $products = DB::connection($this->database)
            ->table($this->tableName)
            ->select($this->pk, 'ean')
            ->orderBy('ean', 'asc')
            ->orderBy('artikelid', 'asc')
            ->paginate(1000);

        $data = $products->getCollection()->pluck('artikelid');

        return response()->json([
            'data' => $data,
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ], 200);
    }

    /**
     * Show a single product.
     *
     * Returns the mapped product fields for the requested article id.
     *
     * @authenticated
     * @header database string required Target CosmoShop connection, must match one of `DB_ARRAY`. Example: jv.de
     * @urlParam id integer required Product identifier (artikelid) to fetch. Example: 12345
     * @response 200 {
     *   "data": {
     *     "article_id": 12345,
     *     "ean": "4000001000017",
     *     "manufacturer": "XL Mebel"
     *   }
     * }
     * @response 404 {
     *   "message": "Item not found"
     * }
     */
    public function read(int $id, Request $request)
    {
        $item = DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->first();

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }
        return response()->json([
            'data' => $this->changeFields($item)
        ], 200);
    }

    /**
     * Permanently delete a product with all relations.
     *
     * Removes the product and its linked prices, content, attributes, stocks, supplier info and more.
     *
     * @authenticated
     * @header database string required Target CosmoShop connection, must match one of `DB_ARRAY`. Example: jv.de
     * @urlParam id integer required Product identifier (artikelid) to delete. Example: 12345
     * @response 200 {
     *   "message": "Item was successfully deleted"
     * }
     * @response 404 {
     *   "error": "Item was not found or delete failed"
     * }
     * @response 500 {
     *   "error": "Delete operation failed",
     *   "message": "..."
     * }
     */
    public function delete(int $id, Request $request)
    {
        $db = DB::connection($this->database);
        $db->beginTransaction();

        try {
            $ausfuehrungIds = $db->table('shopartikelausfuehrungen')
                ->where('artikelid', $id)
                ->pluck('artikelausfuehrungid')
                ->toArray();

            $joinAusfuehrungContent = '';
            $selectAusfuehrungContent = '';
            if (!empty($ausfuehrungIds)) {
                $idsList = implode(',', $ausfuehrungIds);
                $joinAusfuehrungContent = "LEFT JOIN shopartikelausfuehrungcontent
                    ON shopartikelausfuehrungcontent.artikelausfuehrungid IN ({$idsList})";
                $selectAusfuehrungContent = ", shopartikelausfuehrungcontent";
            }

            $sql = "
            DELETE
                shopartikelpreise,
                shopartikelcontent,
                shoprubrikartikel,
                shopartikelbestaende,
                shopartikel_attribute,
                shopartikelproperties,
                shopattribute_zuordnung,
                shopartikelbestand_buchung,
                shopartikelausfuehrungen,
                shopartikellieferanteninfo,
                shopartikelkdgrp,
                {$this->tableName}
                {$selectAusfuehrungContent}
            FROM
                {$this->tableName}
                LEFT JOIN shopartikelpreise ON shopartikelpreise.artikelid = {$this->tableName}.artikelid
                LEFT JOIN shopartikelcontent ON shopartikelcontent.artikelid = {$this->tableName}.artikelid
                LEFT JOIN shoprubrikartikel ON shoprubrikartikel.artikelid = {$this->tableName}.artikelid
                LEFT JOIN shopartikelbestaende ON shopartikelbestaende.artikelid = {$this->tableName}.artikelid
                LEFT JOIN shopartikel_attribute ON shopartikel_attribute.artikelid = {$this->tableName}.artikelid
                LEFT JOIN shopartikelproperties ON shopartikelproperties.artikelid = {$this->tableName}.artikelid
                LEFT JOIN shopattribute_zuordnung ON shopattribute_zuordnung.element = {$this->tableName}.artikelid
                LEFT JOIN shopartikelbestand_buchung ON shopartikelbestand_buchung.artikelid = {$this->tableName}.artikelid
                LEFT JOIN shopartikelausfuehrungen ON shopartikelausfuehrungen.artikelid = {$this->tableName}.artikelid
                LEFT JOIN shopartikellieferanteninfo ON shopartikellieferanteninfo.artikelid = {$this->tableName}.artikelid
                LEFT JOIN shopartikelkdgrp ON shopartikelkdgrp.artikelid = {$this->tableName}.artikelid
                {$joinAusfuehrungContent}
            WHERE
                {$this->tableName}.artikelid = {$id}
            ";
            // Выполняем запрос
            $result = $db->unprepared($sql);

            // Проверяем, существует ли продукт после удаления
            $exists = $db->table($this->tableName)->where('artikelid', $id)->exists();

            if ($exists) {
                $db->rollBack();
                return response()->json([
                    'error' => 'Item was not found or delete failed'
                ], 404);
            }

            $db->commit();

            return response()->json([
                'message' => 'Item was successfully deleted'
            ], 200);
        } catch (\Exception $e) {
            $db->rollBack();
            return response()->json([
                'error' => 'Delete operation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete only the product row.
     *
     * Unlike {@see delete}, this removes the product record without touching related tables.
     *
     * @authenticated
     * @header database string required Target CosmoShop connection, must match one of `DB_ARRAY`. Example: jv.de
     * @urlParam id integer required Product identifier (artikelid) to delete. Example: 12345
     * @response 200 {
     *   "message": "1 items was deleted"
     * }
     * @response 404 {
     *   "message": "Item was not found"
     * }
     */
    public function deleteOnlyProduct(int $id, Request $request)
    {
        $deleted = DB::connection($this->database)->table($this->tableName)->where($this->pk, $id)->delete();
        if (!$deleted)
            abort(404, "Item was not found");
        return response()->json([
            'message' => $deleted . ' items was deleted'
        ], 200);
    }

    /**
     * Mass delete products by id list.
     *
     * Performs a cascading delete for each provided article id inside a single transaction.
     *
     * @authenticated
     * @header database string required Target CosmoShop connection, must match one of `DB_ARRAY`. Example: jv.de
     * @bodyParam ids array required Array of article ids to remove. Example: [12345,12346]
     * @response 200 {
     *   "message": "All items successfully deleted",
     *   "deleted_count": 2
     * }
     * @response 422 {
     *   "error": "Invalid IDs"
     * }
     * @response 500 {
     *   "error": "Delete operation failed",
     *   "message": "..."
     * }
     */
    public function massDelete(Request $request)
    {
        $db = DB::connection($this->database);
        $db->beginTransaction();

        // 1) Получаем и валидируем список ID
        $ids = $request->input('ids');
        if (!is_array($ids) || empty($ids) || !collect($ids)->every(fn($i) => is_numeric($i))) {
            return response()->json(['error' => 'Invalid IDs'], 422);
        }
        // Приводим к целым
        $ids = array_map('intval', $ids);
        $idsList = implode(',', $ids);

        try {
            // 2) Собираем все артикулаусфюрнунг для всех товаров
            $ausfuehrungIds = $db->table('shopartikelausfuehrungen')
                ->whereIn('artikelid', $ids)
                ->pluck('artikelausfuehrungid')
                ->toArray();

            // 3) Готовим секции JOIN и SELECT по content‑таблице (если нужны)
            $joinAusfuehrungContent = '';
            $selectAusfuehrungContent = '';
            if (!empty($ausfuehrungIds)) {
                $idsAusfList = implode(',', $ausfuehrungIds);
                $joinAusfuehrungContent = <<<SQL
    LEFT JOIN shopartikelausfuehrungcontent
    ON shopartikelausfuehrungcontent.artikelausfuehrungid IN ({$idsAusfList})
    SQL;
                $selectAusfuehrungContent = ', shopartikelausfuehrungcontent';
            }

            // 4) Собираем основной DELETE…FROM…WHERE artikelid IN (...)
            $sql = <<<SQL
                DELETE
                    shopartikelpreise,
                    shopartikelcontent,
                    shoprubrikartikel,
                    shopartikelbestaende,
                    shopartikel_attribute,
                    shopartikelproperties,
                    shopattribute_zuordnung,
                    shopartikelbestand_buchung,
                    shopartikelausfuehrungen,
                    shopartikellieferanteninfo,
                    shopartikelkdgrp,
                    {$this->tableName}
                    {$selectAusfuehrungContent}
                FROM
                    {$this->tableName}
                    LEFT JOIN shopartikelpreise           ON shopartikelpreise.artikelid = {$this->tableName}.artikelid
                    LEFT JOIN shopartikelcontent          ON shopartikelcontent.artikelid = {$this->tableName}.artikelid
                    LEFT JOIN shoprubrikartikel           ON shoprubrikartikel.artikelid = {$this->tableName}.artikelid
                    LEFT JOIN shopartikelbestaende        ON shopartikelbestaende.artikelid = {$this->tableName}.artikelid
                    LEFT JOIN shopartikel_attribute       ON shopartikel_attribute.artikelid = {$this->tableName}.artikelid
                    LEFT JOIN shopartikelproperties       ON shopartikelproperties.artikelid = {$this->tableName}.artikelid
                    LEFT JOIN shopattribute_zuordnung     ON shopattribute_zuordnung.element = {$this->tableName}.artikelid
                    LEFT JOIN shopartikelbestand_buchung  ON shopartikelbestand_buchung.artikelid = {$this->tableName}.artikelid
                    LEFT JOIN shopartikelausfuehrungen    ON shopartikelausfuehrungen.artikelid = {$this->tableName}.artikelid
                    LEFT JOIN shopartikellieferanteninfo  ON shopartikellieferanteninfo.artikelid = {$this->tableName}.artikelid
                    LEFT JOIN shopartikelkdgrp            ON shopartikelkdgrp.artikelid = {$this->tableName}.artikelid
                    {$joinAusfuehrungContent}
                WHERE
                    {$this->tableName}.artikelid IN ({$idsList})
                SQL;

            $db->unprepared($sql);

            // 5) Проверяем, что ни одного из этих товаров не осталось
            $remaining = $db->table($this->tableName)
                ->whereIn('artikelid', $ids)
                ->count();

            if ($remaining > 0) {
                $db->rollBack();
                return response()->json([
                    'error' => 'Some items were not deleted',
                    'remaining' => $remaining
                ], 500);
            }

            $db->commit();
            return response()->json([
                'message' => 'All items successfully deleted',
                'deleted_count' => count($ids)
            ], 200);

        } catch (\Exception $e) {
            $db->rollBack();
            return response()->json([
                'error' => 'Delete operation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mass delete products in batches.
     *
     * Processes the incoming ids in chunks of 500 to reduce lock contention during bulk cleanup.
     *
     * @authenticated
     * @header database string required Target CosmoShop connection, must match one of `DB_ARRAY`. Example: jv.de
     * @bodyParam ids array required Array of article ids to remove. Example: [12345,12346,12347]
     * @response 200 {
     *   "message": "Items successfully deleted",
     *   "deleted_count": 3
     * }
     * @response 500 {
     *   "error": "Mass delete failed",
     *   "message": "..."
     * }
     */
    public function massDeleteBatch(Request $request)
    {
        // 1) Неограниченный PHP‑таймаут
        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }
        ini_set('max_execution_time', 0);

        // 2) Валидация входящих ID
        $validated = $request->validate([
            'ids' => 'required|array'
        ]);
        $ids = array_map('intval', $validated['ids']);

        if (empty($ids)) {
            return response()->json(['error' => 'No IDs provided'], 422);
        }

        // 3) Подключаемся к нужной базе
        $db = DB::connection($this->database);

        // 4) Списки связанных таблиц
        $relatedByArtikel = [
            'shopartikelpreise',
            'shopartikelcontent',
            'shoprubrikartikel',
            'shopartikelbestaende',
            'shopartikel_attribute',
            'shopartikelproperties',
            'shopartikelbestand_buchung',
            'shopartikelausfuehrungen',          // нужно удалять сначала, чтобы потом собрать ausfuehrungIds
            'shopartikellieferanteninfo',
            'shopartikelkdgrp',
        ];
        $relatedAusfContent = 'shopartikelausfuehrungcontent';

        // 5) Разбиваем на чанки и выполняем транзакцию
        $chunks = array_chunk($ids, 500);

        try {
            $db->transaction(function () use ($db, $chunks, $relatedByArtikel, $relatedAusfContent) {
                foreach ($chunks as $chunk) {
                    // a) сначала удалить все "ausfuehrung content" по текущему чанку:
                    $ausfIds = $db->table('shopartikelausfuehrungen')
                        ->whereIn('artikelid', $chunk)
                        ->pluck('artikelausfuehrungid')
                        ->toArray();
                    if (!empty($ausfIds)) {
                        $db->table($relatedAusfContent)
                            ->whereIn('artikelausfuehrungid', $ausfIds)
                            ->delete();
                    }

                    // b) затем по каждой таблице, связанной через artikelid
                    foreach ($relatedByArtikel as $table) {
                        $db->table($table)
                            ->whereIn('artikelid', $chunk)
                            ->delete();
                    }

                    // c) и наконец — сама основная таблица
                    $db->table($this->tableName)
                        ->whereIn('artikelid', $chunk)
                        ->delete();
                }
            });

            return response()->json([
                'message' => 'Items successfully deleted',
                'deleted_count' => count($ids),
            ], 200);

        } catch (\Exception $e) {
            // В случае ошибки транзакция откатится автоматически
            return response()->json([
                'error' => 'Mass delete failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resolve article ids by EAN and product name.
     *
     * Useful when an EAN may appear multiple times and must be narrowed down to specific titles.
     *
     * @authenticated
     * @header database string required Target CosmoShop connection, must match one of `DB_ARRAY`. Example: jv.de
     * @bodyParam data array required List of objects with `ean` and `name`. Example: [{"ean":"4000001000017","name":"Oak Dining Table"}]
     * @bodyParam data[].ean string required EAN to match. Example: 4000001000017
     * @bodyParam data[].name string required Product name to match. Example: Oak Dining Table
     * @response 200 [
     *   {
     *     "article_id": 12345,
     *     "name": "Oak Dining Table",
     *     "ean": "4000001000017"
     *   }
     * ]
     */
    public function getByNameAndEan(Request $request)
    {
        $validated = $request->validate([
            'data' => ['required', 'array'],
            'data.*.ean' => ['required'],
            'data.*.name' => ['required', 'string'],
        ]);

        $eans = collect($validated['data'])->pluck('ean')->all();
        $eanToNames = collect($validated['data'])
            ->groupBy('ean')
            ->map(fn ($items) => $items->pluck('name')->unique()->values()->all())
            ->all();

        $db = DB::connection($this->database);

        $productsGrouped = $db->table('shopartikel')
            ->whereIn('ean', $eans)
            ->get(['ean', 'artikelid'])
            ->groupBy('ean');

        $articleIds = $productsGrouped->flatten()->pluck('artikelid')->all();
        $productNames = $db->table('shopartikelcontent')
            ->whereIn('artikelid', $articleIds)
            ->pluck('name', 'artikelid')
            ->all();

        $responseData = [];
        foreach ($productsGrouped as $ean => $products) {
            foreach($products as $product) {
                $articleId = $product->artikelid;
                foreach($eanToNames[$ean] as $name) {
                    if ($productNames[$articleId] == $name) {
                        $responseData[] = [
                            'article_id' => $articleId,
                            'name' => $name,
                            'ean' => $ean
                        ];
                    }
                }
            }
        }
        return response()->json($responseData, 200);
    }

    /**
     * List product ids updated after a specific date.
     *
     * Accepts a day-level date in `d-m-Y` format and returns matching article ids.
     *
     * @authenticated
     * @header database string required Target CosmoShop connection, must match one of `DB_ARRAY`. Example: jv.de
     * @queryParam updated_at string required Date in `d-m-Y` format. Example: 01-03-2024
     * @response 200 {
     *   "count": 42,
     *   "data": [
     *     12345,
     *     12346
     *   ]
     * }
     */
    public function getProductsUpdatedAfter(Request $request) {
        $validated = $request->validate([
            'updated_at' => [
                'required',
                'date_format:d-m-Y', // указываем формат: день-месяц-год
                'before_or_equal:today', // проверка, что дата <= сегодняшней
            ],
        ]);

        $updatedAt = Carbon::createFromFormat('d-m-Y', $validated['updated_at'])->startOfDay();

        $ids = DB::connection($this->database)
            ->table($this->tableName)
            ->where('geaendert', '>', $updatedAt)
            ->get()
            ->pluck('artikelid');

        return response()->json([
            'count' => count($ids),
            'data' => $ids,
        ], 200);
    }

}
