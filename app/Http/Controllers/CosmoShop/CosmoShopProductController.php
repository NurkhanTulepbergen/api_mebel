<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * @group CosmoShop
 * @subgroup CosmoShopProduct
 */
class CosmoShopProductController extends Controller
{
    // public $tableName = \TableName::ProductContent->value;
    public $availableDatabases;
    public $database;
    public $pk = 'id';
    public $mapping = [
        'attribute_id' => 'id',
        'language_code' => 'sprache',
        'attribute_name' => 'name',
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

    function getCount(String $tableName) {
        return DB::connection($this->database)->table($tableName)->count();
    }
    function getItem(String $tableName, int $number) {
        return DB::connection($this->database)->table($tableName)->where('artikelid', $number)->get();
    }
    public function test(Request $request) {
        $db = DB::connection($this->database);
        $tableNames = [
            'shopartikel',
            'shopartikelpreise',
            'shopartikelcontent',
            'shopartikelbestaende',
            'shoprubrikartikel',
            'shopartikelvarianten',
            'shopartikelvariantecontent',
            'shopartikel_attribute',
            'shopattribute',
            'shopattribute_zuordnung',
            'shopattribute_content',
            'shopproperties',
            'shopartikelproperties',
            'shopartikelbestand_buchung',
            'shopartikelausfuehrungen',
            'shopartikelausfuehrungcontent',
            'shopartikelbewertung',
            'shopartikelempfehlung',
            'shopbestseller',
            'shopartikellieferanteninfo',
            'shopartikelkdgrp',
            'shopeinheiten',
            'shopeinheiten_content',
            'shopeinheiten_rel',
        ];
        $stats = [];


        // foreach($tableNames as $name) {
        //     $stats[$name] = $this->getCount($name);
        // }

        return response()->json([
            'stats' => $stats
        ], 201);
    }

    public function ftpTest(Request $request) {
        $validated = $request->validate([
            'small_file' => 'required|file',
            'main_file' => 'required|file',
            'zoomed_file' => 'required|file',
            'article_id' => 'required|integer',
        ]);

        $db = DB::connection($this->database);
        $product = $db->table('shopartikel')
            ->where('artikelid', $validated['article_id'])
            ->first();
        $ean = $product->ean;
        $currentTimestamp = Carbon::now()->timestamp;

        $files = [
            [
                'file' => 'small_file',
                'fileNameWithoutExtension' => $ean.'-'.$currentTimestamp.'-0.',
                'path' => '/v',
            ],
            [
                'file' => 'main_file',
                'fileNameWithoutExtension' => $ean.'-'.$currentTimestamp.'-1.',
                'path' => '/n',
            ],
            [
                'file' => 'main_file',
                'fileNameWithoutExtension' => $ean.'-'.$currentTimestamp.'-2.',
                'path' => '/g',
            ],
            [
                'file' => 'zoomed_file',
                'fileNameWithoutExtension' => $ean.'-'.$currentTimestamp.'.',
                'path' => '/flashzoomer',
            ],
        ];
        $data = [];
        foreach($files as $file) {
            if ($request->hasFile($file['file']) && $request->file($file['file'])->isValid()) {
                $uploadedFile = request()->file($file['file']);
                $extension = $uploadedFile->extension();
                $fileName = $file['fileNameWithoutExtension'].$extension;
                $localPath = $uploadedFile->storeAs('ftp-temp', $fileName);
                $ftpPath = '/jvfurniture.co.uk/cosmoshop/default/pix/a'.$file['path'];
                array_push($data, [
                    'localPath' => $localPath,
                    'ftpPath'   => $ftpPath,
                    'fileName'  => $fileName
                ]);
            }
        }
        // UploadFilesToFtp::dispatch($data, $this->database);
        return response()->json([
            'data' => $data
        ], 200);
    }

    public function last(Request $request) {
        $db = DB::connection($this->database);
        $id = $db->table('shopartikel')->orderby('artikelid', 'desc')->pluck('artikelid')->first();
        $tableNames = [
            'shopartikel',
            'shopartikelpreise',
            'shopartikelcontent',
            'shopartikelbestaende',
            'shoprubrikartikel',
            'shopartikelproperties',
            'shopartikelbestand_buchung',
            'shopartikellieferanteninfo',
            'shopartikelausfuehrungen',
        ];
        $item = [];
        foreach($tableNames as $name) {
            $item[$name] = $this->getItem($name, $id);
        }
        return response()->json([
            'item' => $item
        ], 200);
    }

    public function fullProduct(int $id, Request $request) {
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
        if (! $raw) {
            abort(404, 'Item not found');
        }
        $rawArray = (array) $raw;

        $product = array_diff_key($rawArray, array_flip([
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
            'prices'  => json_decode($raw->prices, true) ?: [],
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


    public function massDeleteProducts(int $id, Request $request) {
        set_time_limit(300);
        $productsId = DB::connection($this->database)
            ->table('shopartikel')
            ->where('artikelid', '>=', $id)
            ->pluck('artikelid')
            ->toArray();
        $db = DB::connection($this->database);
        $fullStats = [];
        foreach($productsId as $productId) {
            $deleted = $db->table('shopartikel')->where('artikelid', $productId)->delete();
            if($deleted) {
                $stats = [];
                // product prices
                $stats['prices'] = $db->table('shopartikelpreise')->where('artikelid', $productId)->delete();
                // content
                $stats['content'] = $db->table('shopartikelcontent')->where('artikelid', $productId)->delete();
                // stock
                $stats['stock'] = $db->table('shoprubrikartikel')->where('artikelid', $productId)->delete();
                // product-categories
                $stats['categories'] = $db->table('shopartikelbestaende')->where('artikelid', $productId)->delete();
                // product-attributes
                $stats['attributes'] = $db->table('shopartikel_attribute')->where('artikelid', $productId)->delete();
                // product-properties
                $stats['properties'] = $db->table('shopartikelproperties')->where('artikelid', $productId)->delete();
                // product-attribute-assignments
                $stats['attribute-assigments'] = $db->table('shopattribute_zuordnung')->where('element', $productId)->delete();
                // stock-movements
                $stats['stock-movements'] = $db->table('shopartikelbestand_buchung')->where('artikelid', $productId)->delete();
                // product-variations
                $variations= $db->table('shopartikelausfuehrungen')->where('artikelid', $productId)->pluck('artikelausfuehrungid')->toArray();
                $stats['variation']= $db->table('shopartikelausfuehrungen')->where('artikelid', $productId)->delete();
                // Product Variation content
                $stats['variation-contents']= $db->table('shopartikelausfuehrungcontent')->whereIn('artikelausfuehrungid', $variations)->delete();
                // product-variations
                $stats['supplier-info'] = $db->table('shopartikellieferanteninfo')->where('artikelid', $productId)->delete();
                // product-customer-group
                $stats['supplier-info'] = $db->table('shopartikelkdgrp')->where('artikelid', $productId)->delete();
                $deleted = $db->table('shopartikel')->where('artikelid', $productId)->delete();
            }
            $fullStats[$productId] = $stats;
        }
        return response()->json([
            'stats' => $fullStats
        ], 200);
    }

    public function getImagesWithDirs(Request $request) {
        $hardImages = DB::connection($this->database)
            ->table('shopmedia')
            ->whereDate('timestamp', now())
            ->whereIn('typ', ['z', 'zg'])
            ->get();

        $data = [];
        $dirs = [];
        $dirsData = [];
        foreach($hardImages as $image) {
            if(!in_array($image->key, $dirs)) {
                $data[$image->key] = [];
                array_push($dirs, $image->key);
                array_push($dirsData, '/jvfurniture.co.uk/cosmoshop/default/pix/a/z/'.$image->key);
            }
            if($image->typ == 'z') {
                array_push($data[$image->key], 'z/'.$image->key.'/'.$image->dateiname.'.'.$image->endung);
            } else {
                array_push($data[$image->key], 'z/'.$image->key.'/g/'.$image->dateiname.'.'.$image->endung);
            }
        }

        return response()->json([
            'count' => sizeof($hardImages),
            'dirs' => $dirsData,
            'data' => $data,
        ], 200);
    }

    public function getImages(Request $request) {
        $basicImages = DB::connection($this->database)
            ->table('shopmedia')
            ->whereDate('timestamp', now())
            ->whereIn('typ', ['v', 'n', 'g', 'flashzoomer'])
            ->get();
        $data = [
            'v' => [],
            'n' => [],
            'g' => [],
            'flashzoomer' => []
        ];
        foreach($basicImages as $image) {
            array_push($data[$image->typ], '/jvfurniture.co.uk/cosmoshop/default/pix/a/'.$image->typ.'/'.$image->dateiname.'.'.$image->endung);
        }

        return response()->json([
            'count' => sizeof($basicImages),
            'images' => $data,
        ], 200);
    }
}
