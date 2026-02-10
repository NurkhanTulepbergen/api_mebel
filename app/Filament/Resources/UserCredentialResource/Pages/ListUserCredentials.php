<?php

namespace App\Filament\Resources\UserCredentialResource\Pages;

use App\Filament\Resources\UserCredentialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserCredentials extends ListRecords
{
    protected static string $resource = UserCredentialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
