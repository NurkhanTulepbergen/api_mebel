<?php

namespace App\Filament\Resources\CurrencyResource\Widgets;

use Filament\Widgets\Widget;

use App\Models\Currency;
use App\Http\Traits\CurrencyTrait;
use Illuminate\Support\Carbon;

class UpdateCurrencies extends Widget
{
    use CurrencyTrait;
    protected static string $view = 'filament.resources.currency-resource.widgets.update-currencies';

    public function getViewData(): array
    {
        $lastUpdatedAt = Currency::max('updated_at');

        $lastUpdated = $lastUpdatedAt
            ? Carbon::parse($lastUpdatedAt)
            : null;

        return [
            'lastUpdated' => $lastUpdated
                ? $lastUpdated->diffForHumans()
                : 'Never',
            'shouldShowButton' => $lastUpdated
                ? $lastUpdated->lt(now()->subHour())
                : true,
        ];
    }

    public function updatePrices(): void
    {
        $this->updateCurrencyRates();
        $this->dispatch('refresh');
    }
}
