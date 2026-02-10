<?php

namespace App\Http\Controllers\Web\CosmoShop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Domain;

use Illuminate\Support\Facades\{
    DB,
};

class SofortController extends Controller {
    public function showForm() {
        return view('cosmoShop.sofort.showForm');
    }
    public function checkProductExistance(Request $request) {
        $validated = $request->validate([
            'ean' => 'required|string|max:13'
        ]);

        $ean = $validated['ean'];

        $domains = Domain::getJv();
        $products = [];
        foreach($domains as $domain) {
            $products[$domain->id] = DB::connection($domain->name)
                ->table('shopartikel')
                ->where('ean', $ean)
                ->where('is_sofort', 1)
                ->exists();
        }
        return $products;
    }

    public function deactivateProducts(Request $request) {
        $validated = $request->validate([
            'ean' => 'required|string|max:13'
        ]);

        $ean = $validated['ean'];

        $domains = Domain::getJv();
        $products = [];
        foreach($domains as $domain) {
            $isUpdated = DB::connection($domain->name)
                ->table('shopartikel')
                ->where('ean', $ean)
                ->where('is_sofort', 1)
                ->update([
                    'inaktiv' => 1
                ]);

            $url = DB::connection($domain->name)
                ->table('shopartikel p')
                ->innerJoin('shopartikelcontent c', 'p.artikelid', '=', 'c.artikelid')
                ->where('p.ean', $ean)
                ->where('p.is_sofort', 1)
                ->select('c.urlkey');

            $products[$domain->id] = [
                'is_updated' => $isUpdated,
                'url' => $url,
            ];
        }
        return $products;
    }
}
