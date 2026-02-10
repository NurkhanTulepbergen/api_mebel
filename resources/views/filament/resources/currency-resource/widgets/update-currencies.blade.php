<x-filament::widget>
    <x-filament::card>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-lg font-semibold">Currency Rates</p>
                <p class="text-sm text-gray-500">Last updated: <strong>{{ $lastUpdated }}</strong></p>
            </div>
            @if ($shouldShowButton)
                <x-filament::button wire:click="updatePrices" color="primary">
                    Update Prices
                </x-filament::button>
            @endif
        </div>
    </x-filament::card>
</x-filament::widget>
