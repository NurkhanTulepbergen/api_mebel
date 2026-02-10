<?php

namespace App\Http\Traits;
use Exception;
use Illuminate\Support\Facades\Http;

use App\Models\Currency;

trait CurrencyTrait {
    public function updateCurrencyRates()
    {
        $data = $this->makeApiCall();

        $eurRate = floatval($data['rates']['EUR']);

        foreach ($data['rates'] as $currency => $rate) {
            if ($currency === 'EUR')
                continue;
            $newRate = floatval($rate) / $eurRate;
            if ($newRate < 1) $newRate = 1;
            Currency::where('code', $currency)->update([
                'rate' => $newRate,
            ]);
        }
    }

    private function makeApiCall()
    {
        $currencyCodes = Currency::get()->pluck('code')->toArray();
        $codes = implode(',', $currencyCodes);
        $apiKey = env("CURRENCY_FREAK_API_KEY", "85c9ef1de06f4c7c8a4511a6fe6ebd5f");

        $response = Http::get('https://api.currencyfreaks.com/v2.0/rates/latest', [
            'apikey' => $apiKey,
            'symbols' => $codes,
        ]);

        if ($response->successful())
            return $response->json();
        else
            throw new Exception('Currency Freak API call error');
    }
}
