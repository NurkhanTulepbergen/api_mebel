<?php

namespace App\Filament\Resources\DatabaseResource\Pages;

use App\Filament\Resources\DatabaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

use App\Http\Traits\DatabaseTrait;

class ListDatabases extends ListRecords
{
    use DatabaseTrait;

    protected static string $resource = DatabaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('regenerate-config')
                ->label('Пересоздать конфиг')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Пересоздать конфиг баз данных?')
                ->modalSubheading('Эта операция может перезаписать текущие настройки. Вы уверены, что хотите продолжить?')
                ->modalButton('Да, пересоздать')
                 ->action(function () {
                    $this->regenerateDatabaseConfig();

                    Notification::make()
                        ->title('Конфиг базы успешно пересоздан ✅')
                        ->success()
                        ->send();
                }),

            Actions\CreateAction::make(),
        ];
    }
}
