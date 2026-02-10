<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\{
    DB,
    Log,
    Redis,
    Config,
};

use App\Models\Domain;

/**
 * Helpers for removing products (and related records) from OpenCart or JV databases.
 */
trait ProductRemoveTrait
{
    /**
     * Delete products and related records from an OpenCart-based store.
     *
     * @return array EANs successfully removed.
     */
    public function massDeleteXl(array $eanList, Domain $domain): array
    {
        $db = DB::connection($domain->name);

        $tables = [
            'oc_product',
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
        $rows = $db->table('oc_product')
            ->whereIn('model', $eanList)
            ->get(['product_id', 'model'])
            ->toArray();

        if(count($rows) == 0)
            return [];

        $eans = array_column($rows, 'model');
        $ids  = array_column($rows, 'product_id');

        $queries = [];
        foreach($ids as $id)
            $queries[] = "product_id={$id}";

        foreach ($tables as $table) {
            $db->table($table)
                ->whereIn('product_id', $ids)
                ->delete();
        }

        $db->table('oc_url_alias')
            ->whereIn('query', $queries)
            ->delete();

        Log::channel('mass-delete')->info("Deleted:", [
            'base' => $domain->name,
            'eans' => $eans,
        ]);
        return $eans;
    }

    /**
     * Delete products and related records from a JV-based store.
     *
     * @return array EANs successfully removed.
     */
    public function massDeleteJv(array $eanList, Domain $domain): array
    {
        $db = DB::connection($domain->name);
        $tables = [
            'shopartikel',
            'shopartikelpreise',
            'shopartikelcontent',
            'shoprubrikartikel',
            'shopartikelbestaende',
            'shopartikel_attribute',
            'shopartikelproperties',
            'shopartikelbestand_buchung',
            'shopartikelausfuehrungen',  // нужно удалять сначала, чтобы потом собрать ausfuehrungIds
            'shopartikellieferanteninfo',
            'shopartikelkdgrp',
        ];
        $relatedAusfContent = 'shopartikelausfuehrungcontent';
        $rows = $db
            ->table('shopartikel')
            ->whereIn('ean', $eanList)
            ->where('is_sofort', 0)
            ->get(['ean', 'artikelid'])
            ->toArray();

        $soforts = $db
            ->table('shopartikel')
            ->whereIn('ean', $eanList)
            ->where('is_sofort', 1)
            ->get(['ean'])
            ->toArray();

        $eans = array_column($rows, 'ean');
        $ids  = array_column($rows, 'artikelid');

        $ausfIds = $db->table('shopartikelausfuehrungen')
            ->whereIn('artikelid', $ids)
            ->pluck('artikelausfuehrungid')
            ->toArray();
        if (!empty($ausfIds)) {
            $db->table($relatedAusfContent)
                ->whereIn('artikelausfuehrungid', $ausfIds)
                ->delete();
        }

        foreach ($tables as $table) {
            $db->table($table)
                ->whereIn('artikelid', $ids)
                ->delete();
        }

        // Удаление СЕО
        $db->table('shopseo')
                ->where('typ', 'a')
                ->whereIn('id', $ids)
                ->delete();

        if(count($soforts)) {
            $sofortEans = array_column($soforts, 'ean');

            Log::channel('sofort')->info("JV ids:", [
                'database' => $domain->name,
                'soforts' => $sofortEans,
            ]);
        }

        Log::channel('mass-delete')->info("JV ids:", [
            'database' => $domain->name,
            'eans' => $eans,
        ]);
        return $eans;
    }
}
