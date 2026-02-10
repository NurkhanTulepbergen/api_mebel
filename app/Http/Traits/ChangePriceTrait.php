<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\{
    DB,
    Log,
};

/**
 * Shared helpers for applying price changes across XL and JV domains.
 */
trait ChangePriceTrait
{
    /**
     * Update XL domain product prices and specials based on provided EAN list.
     *
     * @param array $products
     * @param \App\Models\Domain $domain
     * @return array<int, array<string, mixed>>
     */
    public function xlChangePrice($products, $domain): array
    {
        $db = DB::connection($domain->name);
        $mainProductCase = "CASE";
        $specialCase = "CASE";
        $rate = $domain->currency->rate;
        $eans = array_column($products, 'ean');
        $eanToNewPrice = collect($products)->pluck('price', 'ean')->all();
        $eanToLogInputId = collect($products)->pluck('log_input_id', 'ean')->all();
        $processedProducts = $db->table('oc_product')
            ->whereIn('model', $eans)
            ->get(['model', 'price', 'product_id']);

        $productIds = $processedProducts->pluck('product_id')->all();

        $existingSpecialIds = $db->table('oc_product_special')
            ->whereIn('product_id', $productIds)
            ->pluck('product_id')
            ->flip(); // turns [29601, 29603] into [29601 => 0, 29603 => 1]

        $specialPrices = collect($productIds)
            ->mapWithKeys(fn ($id) => [$id => $existingSpecialIds->has($id)])
            ->all();

        if(!count($processedProducts))
            return [];
        $eans = [];
        $specialProductIds = [];
        $result = [];
        $newSpecialPrices = [];
        foreach ($processedProducts as $product) {
            $ean = $product->model;
            $price = $this->processPrice($eanToNewPrice[$ean], $rate);
            $uvp = $this->processUvp($price);
            $logInputId = $eanToLogInputId[$ean];
            $mainProductCase .= " WHEN model = {$ean} THEN {$uvp}";

            if(!$specialPrices[$product->product_id]) {
                $newSpecialPrices[] = [
                    'product_id' => $product->product_id,
                    'price'      => $uvp,
                ];
            } else {
                $specialProductIds[] = $product->product_id;
                $specialCase .= " WHEN product_id = {$product->product_id} THEN {$price}";
            }

            $eans[] = $ean;
            $result[] = [
                'ean' => $ean,
                'price' => $price,
                'uvp' => $uvp,
                'log_input_id' => $logInputId,
            ];
        }
        $mainProductCase .= " ELSE price END";
        $specialCase    .= " ELSE price END";

        $db->table('oc_product')
            ->whereIn('model', $eans)
            ->update([
                'price' => DB::raw($mainProductCase),
            ]);

        if(count($newSpecialPrices) > 0) {
            DB::table('oc_product_special')
                ->insert($newSpecialPrices);
        }
        if(count($specialProductIds) > 0) {
            $db->table('oc_product_special')
                ->whereIn('product_id', $specialProductIds)
                ->update([
                    'price' => DB::raw($specialCase),
                ]);
        }

        Log::channel('change-price')->info('Change price Trait: ', [
            'requested_products' => $products,
            'database' => $domain->name,
            'processedProducts' => $processedProducts,
            'result' => $result,
        ]);
        return $result;
    }

    /**
     * Push price updates to JV domain schema.
     *
     * @param array $products
     * @param \App\Models\Domain $domain
     * @return array<int, array<string, mixed>>
     */
    public function jvChangePrice(array $products, $domain): array {
        $db = DB::connection($domain->name);
        $rate = $domain->currency->rate;
        $vatRate = 1.19;
        $tables = [
            'cache' => 'shopcache2_article_preview',
            'product' => 'shopartikel',
            'price' => 'shopartikelpreise',
        ];

        // Маппинг ean → price
        $eanToPrice = collect($products)
            ->keyBy('ean')
            ->map(fn($item) => $this->processPrice($item['price'], $rate));
        $eanList = $eanToPrice->keys()->all();
        $eanToLogInputId = collect($products)->pluck('log_input_id', 'ean')->all();

        // Получаем продукты, которые реально есть
        $productsData = $db->table("shopartikel as product")
            ->join('shopcache2_article_preview as cache', 'product.artikelnr', '=', 'cache.nr')
            ->whereIn('product.ean', $eanList)
            ->get(['product.ean', 'cache.prices', 'product.artikelid', 'cache.nr', 'product.artikelnr']);

        $cacheCaseSql = $caseSql = $mainCaseSql = $cacheUvpCase = "CASE";
        $productNumbers = $productIds = $result = [];
        foreach($productsData as $product) {
            $ean = $product->ean;
            $price = $eanToPrice[$ean];
            $productId = $product->artikelid;
            $logInputId = $eanToLogInputId[$ean];
            $caseSql .= " WHEN artikelid = {$productId} THEN {$price}";

            $productNumber = $product->artikelnr;
            $netPrice = $price / $vatRate;
            $vatPrice = $price - $netPrice;
            $json = json_decode($product->prices, true);
            $json['none']['net'] = $netPrice;
            $json['none']['vat'] = $vatPrice;

            $jsonRaw = json_encode($json);

            $cacheCaseSql .= " WHEN nr = '{$product->nr}' THEN '{$jsonRaw}'";
            
            $uvp = $this->processUvp($price);

            $mainCaseSql .= " WHEN artikelid = {$productId} THEN {$uvp}";
            $cacheUvpCase .= " WHEN nr = '{$product->nr}' THEN '{$uvp}'";

            $productNumbers[] = $productNumber;
            $productIds[] = $productId;
            $result[] = [
                'ean' => $ean,
                'price' => $price,
                'log_input_id' => $logInputId,
            ];

        }
        $cacheCaseSql .= " ELSE prices END";
        $caseSql      .= " ELSE preis END";
        $mainCaseSql  .= " ELSE uvp END";
        $cacheUvpCase .= " ELSE rrp END";

        if (!empty($productsData)) {
            $db->table($tables['cache'])
                ->whereIn('nr', $productNumbers)
                ->update([
                    'prices' => DB::raw($cacheCaseSql),
                    'rrp'    => DB::raw($cacheUvpCase),
                ]);
            $db->table($tables['price'])
                ->whereIn('artikelid', $productIds)
                ->update([
                    'preis' => DB::raw($caseSql),
                ]);
            $db->table($tables['product'])
                ->whereIn('artikelid', $productIds)
                ->update([
                    'uvp' => DB::raw($mainCaseSql),
                ]);
        }
        return $result;
    }

    /**
     * Calculate the adjusted price using the domain exchange rate.
     *
     * @param float|int $price
     * @param float|int $rate
     * @return int
     */
    private function processPrice($price, $rate)
    {
        $priceWithRate = (int) ((int)$price * $rate);
        return $priceWithRate - ($priceWithRate % 10) + 9;
    }

    /**
     * Derive UVP (recommended retail price) from the provided price tiers.
     *
     * @param float|int $price
     * @return int
     */
    private function processUvp($price) {
        if ($price >= 5000)
            $value = $price * 1.10;
        elseif ($price >= 2500 && $price <= 4999)
            $value = $price * 1.18;
        elseif ($price >= 1000 && $price <= 2499)
            $value = $price * 1.25;
        else
            $value = $price * 1.35;

        // делим на 10, округляем вверх и умножаем обратно
        return ceil($value / 10) * 10;
    }
}
