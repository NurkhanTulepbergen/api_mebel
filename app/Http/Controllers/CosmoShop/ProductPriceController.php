<?php

namespace App\Http\Controllers\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use App\Models\{
    Domain,
    ExternalCookie,
};
use App\Http\Traits\CosmoShop\CookieTrait;
use Carbon\Carbon;

use Illuminate\Support\Facades\DB;
/**
 * @group CosmoShop
 * @subgroup ProductPrice
 */
class ProductPriceController extends Controller
{
    use CookieTrait;
    private $database;
    public $tableName = \TableName::ProductPrice->value;
    public $pk = 'artikelid';
    public $mapping = [
        'article_id'        => 'artikelid',
        'price_tier'        => 'staffel',
        'price'             => 'preis',
        'percentage'        => 'prozent',
        'price_basis'       => 'basis',
        'filter'            => 'filter',
        'currency'          => 'waehrung',
        'auto_calculate'    => 'auto',
        'tier_id'           => 'staffelid',
        'alternative_price' => 'second_price',
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
            'article_id'        => 'required|integer',
            'price_tier'        => 'integer',
            'price'             => 'decimal:0,10',
            'percentage'        => 'decimal:0,2',
            'price_basis'       => 'string',
            'filter'            => 'string',
            'currency'          => 'string',
            'auto_calculate'    => 'integer',
            'tier_id'           => 'integer',
            'alternative_price' => 'decimal:0,10',

            'with_uvp'          => 'sometimes|boolean'
        ]);

        $validated['with_uvp'] = $request->boolean('with_uvp', false);

        $db = DB::connection($this->database);

        

        $mappedData = [];
        foreach ($validated as $key => $value) {
            if (isset($this->mapping[$key])) {
                $mappedData[$this->mapping[$key]] = $value;
            }
        }
        $db->beginTransaction();
        $db->table($this->tableName)
            ->insert($mappedData);

        $item = $db
            ->table($this->tableName)
            ->where($this->pk, $validated['article_id'])
            ->first();

        if ($validated['with_uvp']) {
            $price = (float) $validated['price'];

            // множитель по порогам
            $multiplier = $price > 5000
                ? 1.10
                : ($price >= 2500
                    ? 1.18
                    : ($price >= 1000 ? 1.25 : 1.35));

            // округление вверх до ближайших 10
            $uvp = (int) (ceil(($price * $multiplier) / 10) * 10);
            $db->table('shopartikel')
                ->where('artikelid', $validated['article_id'])
                ->update([
                    'geaendert' => Carbon::now(),
                    'uvp'       => $uvp,
                ]);
            $db->table('shopcache2_article_preview as c')
                ->join('shopartikel as a', 'c.nr', '=', 'a.artikelnr') // <- если в кэше есть c.artikelid
                ->where('a.artikelid', $validated['article_id'])
                ->update([
                    'rrp' => $uvp,
                ]);
            
        }

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

    public function getPrice(Request $request) {
        $validated = $request->validate([
            'value' => 'required|array'
        ]);
        $items = DB::connection($this->database)
            ->table('shopartikel as product')
            ->join('shopartikelpreise as price', "product.artikelid", '=', 'price.artikelid')
            ->join('shopcache2_article_preview as cache', "product.artikelnr", '=', 'cache.nr')
            ->whereIn("product.artikelid", $validated['value'])
            ->where("price.filter", 'default')
            ->get(['product.artikelid as article_id', 'price.preis as price', 'cache.prices as cache_price']);
        $data = [];
        foreach($items as $item) {
            $json = json_decode($item->cache_price);
            $cachePrice = (float) $json->none->net+ (float) $json->none->vat;
            $data[$item->article_id] = [
                'price' => (float) $item->price,
                'cache_price' => $cachePrice
            ];
        }
        return response()->json($data, 200);
    }

    public function changePrice(int $id, Request $request) {
        $validated = $request->validate([
            'price'     => 'decimal:0,10',

            'with_uvp'  => 'sometimes|boolean',
        ]);

        $validated['with_uvp'] = $request->boolean('with_uvp', false);

        $db = DB::connection($this->database);

        if ($validated['with_uvp']) {
            $price = (float) $validated['price'];

            // множитель по порогам
            $multiplier = $price > 5000
                ? 1.10
                : ($price >= 2500
                    ? 1.18
                    : ($price >= 1000 ? 1.25 : 1.35));

            // округление вверх до ближайших 10
            $uvp = (int) (ceil(($price * $multiplier) / 10) * 10);
            $db->table('shopartikel')
                ->where('artikelid', $id)
                ->update([
                    'geaendert' => Carbon::now(),
                    'uvp'       => $uvp,
                ]);
            $db->table('shopcache2_article_preview as c')
                ->join('shopartikel as a', 'c.nr', '=', 'a.artikelnr') // <- если в кэше есть c.artikelid
                ->where('a.artikelid', $id)
                ->update([
                    'rrp' => $uvp,
                ]);
            
        }

        $productNumber = $db->table('shopartikel')
            ->where('artikelid', $id)
            ->value('artikelnr'); 
        $db->table('shopartikelpreise')
            ->where('artikelid', $id)
            ->update([
                'preis' => $validated['price'],
            ]);

        $jsonRaw = $db->table('shopcache2_article_preview')
            ->where('nr', $productNumber)
            ->value('prices');

        $json = json_decode($jsonRaw, true);
        $vatRate = 1.19;
        $netPrice = $validated['price'] / $vatRate;
        $vatPrice = $validated['price'] - $netPrice;

        $json['none']['net'] = $netPrice;
        $json['none']['vat'] = $vatPrice;

        $jsonRaw = json_encode($json);

        $cacheFields = [
            'prices' => $jsonRaw,
        ];
        if ($validated['with_uvp'])
            $cacheFields['rrp'] = $uvp;

        $jsonRaw = $db->table('shopcache2_article_preview')
            ->where('nr', $productNumber)
            ->update($cacheFields);

        return response()->json([
            'message' => 'updated',
            'price' => $validated['price'],
            'cachePrice' => $json
        ], 200);
    }

    public function massUpdate(Request $request) {
        $validated = $request->validate([
            '*.article_id' => 'required|integer',
            '*.filter'     => 'required|string',
            '*.price_tier' => 'sometimes|integer',
            '*.price'      => 'sometimes|decimal:0,10',
            '*.percentage' => 'sometimes|decimal:0,2',
            '*.price_basis'=> 'sometimes|string',
            '*.currency'   => 'sometimes|string',
            '*.auto_calculate' => 'sometimes|integer',
            '*.tier_id'    => 'sometimes|integer',
            '*.alternative_price' => 'sometimes|decimal:0,10',
        ]);

        $db = DB::connection($this->database);

        $table = $this->tableName;
        $keyFields = ['article_id', 'filter'];
        $updateFields = array_keys($this->mapping);

        $cases = [];
        $whereClauses = [];

        foreach ($updateFields as $field) {
            $cases[$field] = [];
        }

        foreach ($validated as $item) {
            $whereKey = md5($item['article_id'] . '|' . $item['filter']);
            $whereClauses[$whereKey] = [
                'article_id' => (int) $item['article_id'],
                'filter'     => DB::getPdo()->quote($item['filter']),
            ];

            foreach ($updateFields as $field) {
                if (isset($item[$field])) {
                    $dbField = $this->mapping[$field];
                    $val = is_numeric($item[$field]) ? $item[$field] : DB::getPdo()->quote($item[$field]);
                    $cases[$field][] = "WHEN article_id = {$item['product_id']} AND filter = {$whereClauses[$whereKey]['filter']} THEN {$val}";
                }
            }
        }

        $sql = "UPDATE {$table} SET\n";
        $setParts = [];

        foreach ($cases as $field => $caseList) {
            if (!empty($caseList)) {
                $dbField = $this->mapping[$field];
                $setParts[] = "{$dbField} = CASE\n" . implode("\n", $caseList) . "\nEND";
            }
        }

        $sql .= implode(",\n", $setParts);

        // Собираем WHERE IN по всем product_id/filter
        $whereStrings = array_map(function ($pair) {
            return "(article_id = {$pair['product_id']} AND filter = {$pair['filter']})";
        }, $whereClauses);

        $sql .= "\nWHERE " . implode(" OR ", $whereStrings);

        $db->statement($sql);
    }


    public function paginate(Request $request) {
        $products = DB::connection($this->database)
            ->table($this->tableName)
            ->paginate(100);

        $mappedItems = $products->map(function ($item) {
            return $this->changeFields($item);
        });

        return response()->json([
            'data' => $mappedItems
        ], 200);
    }

    public function read(int $id, Request $request) {
        $item = DB::connection($this->database)
            ->table('shopartikel as product')
            ->join('shopartikelpreise as price', "product.artikelid", '=', 'price.artikelid')
            ->join('shopcache2_article_preview as cache', "product.artikelnr", '=', 'cache.nr')
            ->where("product.artikelid", $id)
            ->where("price.filter", 'default')
            ->first(['price.*', 'cache.prices as cache_price']);
        if (!$item)
            return response()->json(['message' => 'Item not found'], 404);
        $item->cache_price = json_decode($item->cache_price);
        return response()->json([
            'data' => $item
        ], 200);
    }

    public function delete(int $id, Request $request) {
        $validated = $request->validate([
            'filter' => 'required|string'
        ]);
        $deleted = DB::connection($this->database)
            ->table($this->tableName)
            ->where($this->pk, $id)
            ->where('filter', $validated['filter'])
            ->delete();
        if(!$deleted) abort(404, "Item was not found");
        return response()->json([
            'message' => $deleted.' items was deleted'
        ], 200);
    }
}

