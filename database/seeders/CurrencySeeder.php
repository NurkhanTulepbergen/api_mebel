<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Currency;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'EUR', 'name' => 'Euro'],
            ['code' => 'TRY', 'name' => 'Turkish Lira'],
            ['code' => 'RUB', 'name' => 'Russian Ruble'],
            ['code' => 'AED', 'name' => 'UAE Dirham'],
            ['code' => 'CHF', 'name' => 'Swiss Franc'],
            ['code' => 'GBP', 'name' => 'Pound Sterling'],
            ['code' => 'CZK', 'name' => 'Czech Koruna'],
            ['code' => 'PLN', 'name' => 'Polish Zloty'],
            ['code' => 'DKK', 'name' => 'Danish Krone'],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                ['name' => $currency['name']]
            );
        }
    }
}
